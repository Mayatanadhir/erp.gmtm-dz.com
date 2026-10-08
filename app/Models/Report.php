<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\OamMetrologyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Report extends Model
{
    protected $table = 'reports';

    /**
     * Non-reportable instrument types excluded from standard loop reports.
     * (Chromatographs, Provers, and Standard Test Measure Gauges are managed in dedicated metrology modules)
     */
    public const NON_REPORTABLE_TYPES = [
        'Chromatograph',
        'chromatograph',
        'Prover',
        'prover',
        'StandardGauge',
        'standard_gauge',
        'TestMeasure',
        'test_measure',
        'Guge_etalon',
        'Gauge',
    ];

    protected $fillable = [
        'mission_id',
        'report_number',
        'status',
        'excluded_instrument_ids',
        'default_calibrators',
    ];

    protected $casts = [
        'excluded_instrument_ids' => 'array',
        'default_calibrators' => 'array',
    ];

    /**
     * The "booted" method of the model.
     * Auto-inject verification points on creation, and cascade delete on deletion.
     */
    protected static function booted(): void
    {
        // 1. التهيئة والحقن التلقائي لنقاط المعايرة الأولية لجميع أجهزة التقرير بقيم فارغة (Null) عند إنشاء التقرير
        static::created(function (Report $report) {
            $report->initializeVerificationPoints();
        });

        // 2. حذف جميع البيانات المترولوجية والنقاط المرتبطة بالتقرير عند حذفه
        static::deleting(function (Report $report) {
            DB::transaction(function () use ($report) {
                // 1. حذف جميع نقاط ومعايرات ومعدات مرسلات الضغط والحرارة (Transmitter Verifications)
                $transmitterVerifIds = DB::table('transmitter_verifications')
                    ->where('report_mission_id', $report->id)
                    ->pluck('id');

                if ($transmitterVerifIds->isNotEmpty()) {
                    DB::table('transmitter_verification_calibrators')
                        ->whereIn('verification_id', $transmitterVerifIds)
                        ->delete();

                    DB::table('transmitter_verification_points')
                        ->whereIn('verification_id', $transmitterVerifIds)
                        ->delete();

                    DB::table('transmitter_verifications')
                        ->whereIn('id', $transmitterVerifIds)
                        ->delete();
                }

                // 2. حذف جميع نقاط ومعايرات ومعدات المسابير الحرارية (Probe Verifications)
                $probeVerifIds = DB::table('probe_verifications')
                    ->where('report_mission_id', $report->id)
                    ->pluck('id');

                if ($probeVerifIds->isNotEmpty()) {
                    DB::table('probe_verification_calibrators')
                        ->whereIn('verification_id', $probeVerifIds)
                        ->delete();

                    DB::table('probe_verification_points')
                        ->whereIn('verification_id', $probeVerifIds)
                        ->delete();

                    DB::table('probe_verifications')
                        ->whereIn('id', $probeVerifIds)
                        ->delete();
                }

                // 3. حذف جميع نقاط ومعايرات ومعدات حاسبات التدفق وقنواتها (Flow Computer Verifications)
                $fcVerifIds = DB::table('flow_computer_verifications')
                    ->where('report_mission_id', $report->id)
                    ->pluck('id');

                if ($fcVerifIds->isNotEmpty()) {
                    DB::table('flow_computer_verification_calibrators')
                        ->whereIn('verification_id', $fcVerifIds)
                        ->delete();

                    DB::table('flow_computer_verification_points')
                        ->whereIn('verification_id', $fcVerifIds)
                        ->delete();

                    DB::table('flow_computer_verifications')
                        ->whereIn('id', $fcVerifIds)
                        ->delete();
                }

                // 4. حذف سجلات ربط الأجهزة بالتقرير من جدول report_instruments
                if (DB::getSchemaBuilder()->hasTable('report_instruments')) {
                    DB::table('report_instruments')
                        ->where('report_mission_id', $report->id)
                        ->delete();
                }
            });
        });
    }

    /**
     * تهيئة وحقن نقاط المعايرة الأولية لجميع أجهزة التقرير بقيم فارغة (Null)
     */
    public function initializeVerificationPoints(): void
    {
        $mission = $this->mission()->with('site.instruments.specifications.grandeur')->first();
        if (! $mission || ! $mission->site) {
            return;
        }

        $allInstruments = $mission->site->instruments()
            ->where('status', 'active')
            ->with(['specifications.grandeur', 'linkedTransmitters.specifications.grandeur'])
            ->get();

        $excludedIds = $this->excluded_instrument_ids ?? [];
        $includedInstruments = $allInstruments->reject(function ($inst) use ($excludedIds) {
            $typeVal = $inst->instrument_type instanceof \BackedEnum ? $inst->instrument_type->value : (string) $inst->instrument_type;
            if (in_array($inst->id, $excludedIds)) {
                return true;
            }

            return in_array($typeVal, self::NON_REPORTABLE_TYPES, true);
        })->values();

        $oamService = new OamMetrologyService;

        DB::transaction(function () use ($includedInstruments, $oamService) {
            $seq = 1;
            foreach ($includedInstruments as $inst) {
                $typeVal = strtolower($inst->instrument_type instanceof \BackedEnum ? $inst->instrument_type->value : (string) $inst->instrument_type);

                // 1. تسجيل ربط الجهاز بالتقرير في report_instruments
                if (DB::getSchemaBuilder()->hasTable('report_instruments')) {
                    DB::table('report_instruments')->updateOrInsert(
                        ['report_mission_id' => $this->id, 'instrument_id' => $inst->id],
                        ['sequence' => $seq++, 'created_at' => now(), 'updated_at' => now()]
                    );
                }

                $specs = $inst->specifications->first();
                $min = (float) ($specs->range_min ?? 0);
                $max = (float) ($specs->range_max ?? 100);
                $span = $max - $min;
                $grandeur = $specs?->grandeur?->name ?? 'Pression';
                $pressureType = $inst->measurement_type ?? 'Relative';
                $fluidType = $inst->fluid_type instanceof \BackedEnum ? $inst->fluid_type->value : (string) ($inst->fluid_type ?? 'Liquid');

                if ($typeVal === 'transmitter') {
                    $existingVerif = DB::table('transmitter_verifications')
                        ->where('report_mission_id', $this->id)
                        ->where('instrument_id', $inst->id)
                        ->first();

                    if (! $existingVerif) {
                        $verifId = DB::table('transmitter_verifications')->insertGetId([
                            'instrument_id' => $inst->id,
                            'report_mission_id' => $this->id,
                            'verification_date' => now()->toDateString(),
                            'ambient_temperature' => null,
                            'ambient_pressure' => null,
                            'overall_status' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $percentages = [0.0, 25.0, 50.0, 75.0, 100.0, 100.0, 75.0, 50.0, 25.0, 0.0];
                        $points = [];
                        foreach ($percentages as $idx => $pct) {
                            $refVal = $min + ($pct / 100.0) * $span;
                            $eval = $oamService->evaluateTransmitter(
                                $span, $min, $refVal, null, null,
                                $fluidType, $inst->technology, $grandeur, $pressureType, 0.0
                            );
                            $emtLimit = (float) ($eval['emt'] ?? 0.0);

                            $points[] = [
                                'verification_id' => $verifId,
                                'step_order' => $idx + 1,
                                'cycle_phase' => ($idx + 1 <= 5) ? 'Ascending' : 'Descending',
                                'applied_percentage' => $pct,
                                'reference_value' => $refVal,
                                'measured_signal' => null,
                                'indicated_value' => null,
                                'absolute_error' => null,
                                'emt_limit' => $emtLimit,
                                'is_conforme' => null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        DB::table('transmitter_verification_points')->insert($points);
                    }

                } elseif ($typeVal === 'probe') {
                    $existingVerif = DB::table('probe_verifications')
                        ->where('report_mission_id', $this->id)
                        ->where('instrument_id', $inst->id)
                        ->first();

                    if (! $existingVerif) {
                        $verifId = DB::table('probe_verifications')->insertGetId([
                            'instrument_id' => $inst->id,
                            'report_mission_id' => $this->id,
                            'verification_date' => now()->toDateString(),
                            'ambient_temperature' => null,
                            'ambient_pressure' => null,
                            'overall_status' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $percentages = [0.0, 25.0, 50.0, 75.0, 100.0];
                        $points = [];
                        foreach ($percentages as $idx => $pct) {
                            $refTemp = $min + ($pct / 100.0) * $span;
                            $emtLimit = 0.15 + (0.002 * abs($refTemp));

                            $points[] = [
                                'verification_id' => $verifId,
                                'step_order' => $idx + 1,
                                'cycle_phase' => 'Ascending',
                                'reference_temperature' => $refTemp,
                                'measured_resistance' => null,
                                'indicated_temperature' => null,
                                'absolute_error' => null,
                                'emt_limit' => $emtLimit,
                                'is_conforme' => null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        DB::table('probe_verification_points')->insert($points);
                    }

                } elseif ($typeVal === 'flowcomputer' || $typeVal === 'flow_computer') {
                    $linkedTransmitters = $inst->linkedTransmitters()->with('specifications.grandeur')->get();
                    $percentages = [0.0, 25.0, 50.0, 75.0, 100.0, 100.0, 75.0, 50.0, 25.0, 0.0];

                    foreach ($linkedTransmitters as $linked) {
                        $existingVerif = DB::table('flow_computer_verifications')
                            ->where('report_mission_id', $this->id)
                            ->where('instrument_id', $inst->id)
                            ->where('simulated_transmitter_id', $linked->id)
                            ->first();

                        if ($existingVerif) {
                            continue;
                        }

                        $lSpecs = $linked->specifications->first();
                        $tMin = (float) ($lSpecs->range_min ?? 0);
                        $tMax = (float) ($lSpecs->range_max ?? 100);
                        $tSpan = $tMax - $tMin;
                        $tGrandeur = $lSpecs?->grandeur?->name ?? 'Pression';

                        $verifId = DB::table('flow_computer_verifications')->insertGetId([
                            'instrument_id' => $inst->id,
                            'simulated_transmitter_id' => $linked->id,
                            'shunt_resistance' => 250.00,
                            'report_mission_id' => $this->id,
                            'verification_date' => now()->toDateString(),
                            'ambient_temperature' => null,
                            'ambient_pressure' => null,
                            'overall_status' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $points = [];
                        foreach ($percentages as $idx => $pct) {
                            $expectedmA = 4.0 + ($pct / 100.0) * 16.0;
                            $expectedVal = $tMin + ($pct / 100.0) * $tSpan;
                            $eval = $oamService->evaluateADC($tSpan, $tMin, null, 250.0, null, $fluidType, $tGrandeur);
                            $emtLimit = (float) ($eval['emt'] ?? 0.0);

                            $points[] = [
                                'verification_id' => $verifId,
                                'step_order' => $idx + 1,
                                'cycle_phase' => ($idx + 1 <= 5) ? 'Ascending' : 'Descending',
                                'applied_percentage' => $pct,
                                'expected_signal' => $expectedmA,
                                'measured_signal' => null,
                                'expected_value' => $expectedVal,
                                'indicated_value' => null,
                                'absolute_error' => null,
                                'emt_limit' => $emtLimit,
                                'is_conforme' => null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        DB::table('flow_computer_verification_points')->insert($points);
                    }
                }
            }
        });
    }

    // ==========================================
    // Relations
    // ==========================================

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
    }

    public function transmitterVerifications(): HasMany
    {
        return $this->hasMany(TransmitterVerification::class, 'report_mission_id');
    }

    public function probeVerifications(): HasMany
    {
        return $this->hasMany(ProbeVerification::class, 'report_mission_id');
    }

    public function flowComputerVerifications(): HasMany
    {
        return $this->hasMany(FlowComputerVerification::class, 'report_mission_id');
    }

    public function chromatographVerifications(): HasMany
    {
        return $this->hasMany(ChromatographVerification::class, 'report_mission_id');
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('report_number', 'like', "%{$search}%");
            })
            ->when($filters['category'] ?? null, function ($q, $category) {
                if ($category === 'chromatograph') {
                    $q->where(function ($sub) {
                        $sub->where('report_number', 'like', 'RPT-CPG%')
                            ->orWhere('report_number', 'like', 'RPT-GC%');
                    });
                } elseif ($category === 'prover') {
                    $q->where(function ($sub) {
                        $sub->where('report_number', 'like', 'RPT-PRV%')
                            ->orWhere('report_number', 'like', 'RPT-PROV%');
                    });
                } elseif ($category === 'instruments') {
                    $q->where(function ($sub) {
                        $sub->where('report_number', 'not like', 'RPT-CPG%')
                            ->where('report_number', 'not like', 'RPT-GC%')
                            ->where('report_number', 'not like', 'RPT-PRV%')
                            ->where('report_number', 'not like', 'RPT-PROV%');
                    });
                }
            })
            ->when($filters['mission_id'] ?? null, function ($q, $missionId) {
                $q->where('mission_id', $missionId);
            })
            ->when($filters['date_from'] ?? null, function ($q, $dateFrom) {
                $q->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function ($q, $dateTo) {
                $q->whereDate('created_at', '<=', $dateTo);
            })
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            });
    }

    // ==========================================
    // Accessors & Helpers
    // ==========================================

    public function getCategoryAttribute(): string
    {
        if (isset($this->attributes['category']) && ! empty($this->attributes['category'])) {
            return $this->attributes['category'];
        }

        if (str_starts_with($this->report_number, 'RPT-CPG') || str_starts_with($this->report_number, 'RPT-GC')) {
            return 'chromatograph';
        }

        if (str_starts_with($this->report_number, 'RPT-PRV') || str_starts_with($this->report_number, 'RPT-PROV')) {
            return 'prover';
        }

        return 'instruments';
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'chromatograph' => __('Chromatographes CPG'),
            'prover' => __('Tubes Étalons & Prover'),
            default => __('Instruments Industriels'),
        };
    }

    public function getCategoryBadgeVariantAttribute(): string
    {
        return match ($this->category) {
            'chromatograph' => 'info',
            'prover' => 'warning',
            default => 'primary',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
            'progress' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
            default => 'bg-gray-500/10 text-gray-600 dark:text-gray-400',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'completed' => __('Completed'),
            'progress' => __('In Progress'),
            default => __('In Progress'),
        };
    }

    /**
     * Get the count of active instruments participating in this report
     */
    public function getActiveInstrumentsCountAttribute(): int
    {
        if (! $this->mission || ! $this->mission->site) {
            $count = DB::table('report_instruments')->where('report_mission_id', $this->id)->count();

            return $count > 0 ? $count : 0;
        }

        $allInstruments = $this->mission->site->instruments;
        $excludedIds = $this->excluded_instrument_ids ?? [];

        if (empty($excludedIds)) {
            return $allInstruments->count();
        }

        return $allInstruments->reject(fn ($inst) => in_array($inst->id, $excludedIds))->count();
    }

    /**
     * Get default calibrators for a specific instrument from report configuration.
     *
     * @return array<int|null, int|null>
     */
    public function getDefaultCalibratorsForInstrument(Instrument $instrument, ?Instrument $channelTransmitter = null): array
    {
        $defaults = $this->default_calibrators ?? [];
        if (empty($defaults)) {
            return [null, null];
        }

        $type = strtolower($instrument->instrument_type instanceof \BackedEnum ? $instrument->instrument_type->value : (string) $instrument->instrument_type);
        $pv = strtolower($instrument->process_variable instanceof \BackedEnum ? $instrument->process_variable->value : (string) ($instrument->process_variable ?? ''));
        $specs = $instrument->specifications?->first();
        $grandeur = $specs?->grandeur?->name ?? 'Pression';

        if ($type === 'transmitter') {
            $isTemp = ($pv === 'temperature') || (stripos($grandeur, 'Temp') !== false);
            $mt = $instrument->measurement_type ?? '';
            $tag = $instrument->tag_number ?? '';
            $isDP = ($mt === 'Differential') || (stripos($tag, 'PDT') !== false) || (stripos($tag, 'DP') !== false) || (stripos($grandeur, 'Diff') !== false);

            if ($isTemp) {
                $c1 = ! empty($defaults['temperature_calibrator_1']) ? (int) $defaults['temperature_calibrator_1'] : null;
                $c2 = ! empty($defaults['temperature_calibrator_2']) ? (int) $defaults['temperature_calibrator_2'] : null;

                return [$c1, $c2];
            } elseif ($isDP) {
                $c1 = ! empty($defaults['dp_calibrator_1']) ? (int) $defaults['dp_calibrator_1'] : (! empty($defaults['pressure_calibrator_1']) ? (int) $defaults['pressure_calibrator_1'] : null);
                $c2 = ! empty($defaults['dp_calibrator_2']) ? (int) $defaults['dp_calibrator_2'] : (! empty($defaults['pressure_calibrator_2']) ? (int) $defaults['pressure_calibrator_2'] : null);

                return [$c1, $c2];
            } else {
                $c1 = ! empty($defaults['pressure_calibrator_1']) ? (int) $defaults['pressure_calibrator_1'] : null;
                $c2 = ! empty($defaults['pressure_calibrator_2']) ? (int) $defaults['pressure_calibrator_2'] : null;

                return [$c1, $c2];
            }
        } elseif ($type === 'probe') {
            $c1 = ! empty($defaults['probe_calibrator_1']) ? (int) $defaults['probe_calibrator_1'] : null;
            $c2 = ! empty($defaults['probe_calibrator_2']) ? (int) $defaults['probe_calibrator_2'] : null;

            return [$c1, $c2];
        } elseif ($type === 'flowcomputer' || $type === 'flow_computer') {
            $c1 = ! empty($defaults['flow_computer_calibrator_1'])
                ? (int) $defaults['flow_computer_calibrator_1']
                : (! empty($defaults['pressure_calibrator_2']) ? (int) $defaults['pressure_calibrator_2'] : null);

            $targetTransmitter = $channelTransmitter;
            if ($targetTransmitter) {
                $chSpecs = $targetTransmitter->specifications?->first();
                $chGrandeur = $chSpecs?->grandeur?->name ?? '';
                $chSymbol = $chSpecs?->grandeur?->symbol ?? '';
                $chPv = strtolower($targetTransmitter->process_variable instanceof \BackedEnum ? $targetTransmitter->process_variable->value : (string) ($targetTransmitter->process_variable ?? ''));
                $chMt = $targetTransmitter->measurement_type ?? '';
                $chTag = $targetTransmitter->tag_number ?? '';

                $isTemp = ($chPv === 'temperature') || stripos($chGrandeur, 'Temp') !== false || in_array(strtolower((string) $chSymbol), ['°c', 'c', 'k']);
                $isDP = ($chMt === 'Differential') || stripos($chTag, 'PDT') !== false || stripos($chTag, 'DP') !== false || stripos($chGrandeur, 'Diff') !== false;

                if ($isTemp) {
                    $c2 = ! empty($defaults['flow_computer_temp_calibrator'])
                        ? (int) $defaults['flow_computer_temp_calibrator']
                        : (! empty($defaults['temperature_calibrator_1']) ? (int) $defaults['temperature_calibrator_1'] : null);
                } elseif ($isDP) {
                    $c2 = ! empty($defaults['flow_computer_dp_calibrator'])
                        ? (int) $defaults['flow_computer_dp_calibrator']
                        : (! empty($defaults['dp_calibrator_1']) ? (int) $defaults['dp_calibrator_1'] : (! empty($defaults['pressure_calibrator_1']) ? (int) $defaults['pressure_calibrator_1'] : null));
                } else {
                    $c2 = ! empty($defaults['flow_computer_pressure_calibrator'])
                        ? (int) $defaults['flow_computer_pressure_calibrator']
                        : (! empty($defaults['pressure_calibrator_1']) ? (int) $defaults['pressure_calibrator_1'] : null);
                }
            } else {
                $c2 = ! empty($defaults['flow_computer_pressure_calibrator'])
                    ? (int) $defaults['flow_computer_pressure_calibrator']
                    : (! empty($defaults['flow_computer_calibrator_2']) ? (int) $defaults['flow_computer_calibrator_2'] : null);
            }

            return [$c1, $c2];
        }

        return [null, null];
    }

    /**
     * Apply default calibrators to all verifications in this report.
     */
    public function syncDefaultCalibratorsToAllInstruments(): int
    {
        $defaults = $this->default_calibrators ?? [];
        if (empty($defaults)) {
            return 0;
        }

        $count = 0;
        $this->loadMissing(['mission.site.instruments.specifications.grandeur']);
        $instruments = $this->mission?->site?->instruments ?? collect();
        $excluded = $this->excluded_instrument_ids ?? [];

        foreach ($instruments as $inst) {
            if (in_array($inst->id, $excluded)) {
                continue;
            }

            $type = strtolower($inst->instrument_type instanceof \BackedEnum ? $inst->instrument_type->value : (string) $inst->instrument_type);

            if ($type === 'transmitter') {
                $rawCals = $this->getDefaultCalibratorsForInstrument($inst);
                $c1 = ! empty($rawCals[0]) ? (int) $rawCals[0] : null;
                $c2 = ! empty($rawCals[1]) ? (int) $rawCals[1] : null;

                $verif = TransmitterVerification::where('report_mission_id', $this->id)
                    ->where('instrument_id', $inst->id)
                    ->first();
                if ($verif) {
                    $verif->syncCalibratorByRole($c1, 1);
                    $verif->syncCalibratorByRole($c2, 2);
                    $count++;
                }
            } elseif ($type === 'probe') {
                $rawCals = $this->getDefaultCalibratorsForInstrument($inst);
                $c1 = ! empty($rawCals[0]) ? (int) $rawCals[0] : null;
                $c2 = ! empty($rawCals[1]) ? (int) $rawCals[1] : null;

                $verif = ProbeVerification::where('report_mission_id', $this->id)
                    ->where('instrument_id', $inst->id)
                    ->first();
                if ($verif) {
                    $verif->syncCalibratorByRole($c1, 1);
                    $verif->syncCalibratorByRole($c2, 2);
                    $count++;
                }
            } elseif ($type === 'flowcomputer' || $type === 'flow_computer') {
                $verifs = FlowComputerVerification::with('simulatedTransmitter.specifications.grandeur')
                    ->where('report_mission_id', $this->id)
                    ->where('instrument_id', $inst->id)
                    ->get();
                foreach ($verifs as $verif) {
                    $rawCals = $this->getDefaultCalibratorsForInstrument($inst, $verif->simulatedTransmitter);
                    $c1 = ! empty($rawCals[0]) ? (int) $rawCals[0] : null;
                    $c2 = ! empty($rawCals[1]) ? (int) $rawCals[1] : null;

                    $verif->syncCalibratorByRole($c1, 1);
                    $verif->syncCalibratorByRole($c2, 2);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Auto-generate the next report number for the current year by category.
     * Format: RPT-YYYY-NNN, RPT-CPG-YYYY-NNN, or RPT-PRV-YYYY-NNN
     */
    public static function generateReportNumber(?string $category = 'instruments'): string
    {
        $year = now()->year;
        $prefix = match ($category) {
            'chromatograph' => "RPT-CPG-{$year}-",
            'prover' => "RPT-PRV-{$year}-",
            default => "RPT-{$year}-",
        };

        $last = static::query()
            ->where('report_number', 'like', "{$prefix}%")
            ->orderByDesc('report_number')
            ->first();

        $next = 1;
        if ($last) {
            $parts = explode('-', $last->report_number);
            $next = (int) end($parts) + 1;
        }

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
