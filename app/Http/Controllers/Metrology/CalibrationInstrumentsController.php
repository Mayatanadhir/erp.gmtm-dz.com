<?php

declare(strict_types=1);

namespace App\Http\Controllers\Metrology;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calibration\StoreFlowComputerRequest;
use App\Http\Requests\Calibration\StoreProbeRequest;
use App\Http\Requests\Calibration\StoreTransmitterRequest;
use App\Models\ChromatographVerification;
use App\Models\FlowComputerVerification;
use App\Models\FlowComputerVerificationPoint;
use App\Models\Instrument;
use App\Models\InstrumentSpecification;
use App\Models\Mission;
use App\Models\ProbeVerification;
use App\Models\ProbeVerificationPoint;
use App\Models\Report;
use App\Models\TransmitterVerification;
use App\Models\TransmitterVerificationPoint;
use App\Services\CalibratorResolutionService;
use App\Services\InterpolationService;
use App\Services\OamMetrologyService;
use App\Services\SvgChartService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

final class CalibrationInstrumentsController extends Controller
{
    public function __construct(
        protected CalibratorResolutionService $calibratorService,
        protected OamMetrologyService $oamService,
        protected SvgChartService $chartService,
        protected InterpolationService $interpolationService
    ) {}

    /**
     * عرض جدول المعايرات المنجزة للأجهزة وحالتها المترولوجية
     */
    public function index(Request $request): View
    {
        Gate::authorize('view reports');

        $search = $request->filled('search') ? trim((string) $request->input('search')) : null;
        $typeFilter = $request->filled('type') ? (string) $request->input('type') : 'all';
        $statusFilter = $request->filled('status') ? (string) $request->input('status') : 'all';
        $reportFilter = $request->filled('report_id') ? $request->input('report_id') : null;
        $missionFilter = $request->filled('mission_id') ? $request->input('mission_id') : null;
        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 15;
        }

        // 1. استرجاع معايرات المرسلات (Transmitter Verifications)
        $transmitterVerifs = collect();
        if ($typeFilter === 'all' || $typeFilter === 'transmitter') {
            $query = TransmitterVerification::with([
                'instrument.specifications.grandeur',
                'instrument.site',
                'report.mission.site',
                'points',
                'calibrators',
            ]);
            if ($reportFilter) {
                $query->where('report_mission_id', $reportFilter);
            }
            if ($missionFilter) {
                $query->whereHas('report', function ($rq) use ($missionFilter) {
                    $rq->where('mission_id', $missionFilter);
                });
            }
            if ($statusFilter === 'conforme') {
                $query->where('overall_status', true);
            } elseif ($statusFilter === 'non_conforme') {
                $query->where('overall_status', false);
            } elseif ($statusFilter === 'en_cours') {
                $query->whereNull('overall_status');
            }
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('instrument', function ($iq) use ($search) {
                        $iq->where('tag_number', 'like', "%{$search}%")
                            ->orWhere('serial_number', 'like', "%{$search}%");
                    })->orWhereHas('report', function ($rq) use ($search) {
                        $rq->where('report_number', 'like', "%{$search}%")
                            ->orWhereHas('mission', function ($mq) use ($search) {
                                $mq->where('reference', 'like', "%{$search}%")
                                    ->orWhereHas('site', function ($sq) use ($search) {
                                        $sq->where('short_name', 'like', "%{$search}%")
                                            ->orWhere('site_code', 'like', "%{$search}%");
                                    });
                            });
                    });
                });
            }
            $transmitterVerifs = $query->get()->map(fn ($v) => $this->transformVerification($v, 'transmitter'));
        }

        // 2. استرجاع معايرات مسابير الحرارة (Probe Pt100 Verifications)
        $probeVerifs = collect();
        if ($typeFilter === 'all' || $typeFilter === 'probe') {
            $query = ProbeVerification::with([
                'instrument.specifications.grandeur',
                'instrument.site',
                'report.mission.site',
                'points',
                'calibrators',
            ]);
            if ($reportFilter) {
                $query->where('report_mission_id', $reportFilter);
            }
            if ($missionFilter) {
                $query->whereHas('report', function ($rq) use ($missionFilter) {
                    $rq->where('mission_id', $missionFilter);
                });
            }
            if ($statusFilter === 'conforme') {
                $query->where('overall_status', true);
            } elseif ($statusFilter === 'non_conforme') {
                $query->where('overall_status', false);
            } elseif ($statusFilter === 'en_cours') {
                $query->whereNull('overall_status');
            }
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('instrument', function ($iq) use ($search) {
                        $iq->where('tag_number', 'like', "%{$search}%")
                            ->orWhere('serial_number', 'like', "%{$search}%");
                    })->orWhereHas('report', function ($rq) use ($search) {
                        $rq->where('report_number', 'like', "%{$search}%")
                            ->orWhereHas('mission', function ($mq) use ($search) {
                                $mq->where('reference', 'like', "%{$search}%")
                                    ->orWhereHas('site', function ($sq) use ($search) {
                                        $sq->where('short_name', 'like', "%{$search}%")
                                            ->orWhere('site_code', 'like', "%{$search}%");
                                    });
                            });
                    });
                });
            }
            $probeVerifs = $query->get()->map(fn ($v) => $this->transformVerification($v, 'probe'));
        }

        // 3. استرجاع معايرات حاسبات التدفق (Flow Computer Verifications)
        $fcVerifs = collect();
        if ($typeFilter === 'all' || $typeFilter === 'flow_computer') {
            $query = FlowComputerVerification::with([
                'instrument.specifications.grandeur',
                'instrument.site',
                'report.mission.site',
                'points',
                'calibrators',
            ]);
            if ($reportFilter) {
                $query->where('report_mission_id', $reportFilter);
            }
            if ($missionFilter) {
                $query->whereHas('report', function ($rq) use ($missionFilter) {
                    $rq->where('mission_id', $missionFilter);
                });
            }
            if ($statusFilter === 'conforme') {
                $query->where('overall_status', true);
            } elseif ($statusFilter === 'non_conforme') {
                $query->where('overall_status', false);
            } elseif ($statusFilter === 'en_cours') {
                $query->whereNull('overall_status');
            }
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('instrument', function ($iq) use ($search) {
                        $iq->where('tag_number', 'like', "%{$search}%")
                            ->orWhere('serial_number', 'like', "%{$search}%");
                    })->orWhereHas('report', function ($rq) use ($search) {
                        $rq->where('report_number', 'like', "%{$search}%")
                            ->orWhereHas('mission', function ($mq) use ($search) {
                                $mq->where('reference', 'like', "%{$search}%")
                                    ->orWhereHas('site', function ($sq) use ($search) {
                                        $sq->where('short_name', 'like', "%{$search}%")
                                            ->orWhere('site_code', 'like', "%{$search}%");
                                    });
                            });
                    });
                });
            }
            $fcVerifs = $query->get()->map(fn ($v) => $this->transformVerification($v, 'flow_computer'));
        }

        // دمج وترتيب كافة المعايرات حسب تاريخ الإجراء
        $allVerifications = $transmitterVerifs->concat($probeVerifs)->concat($fcVerifs)
            ->sortByDesc('verification_date_raw')
            ->values();

        // حساب الإحصائيات العامة (KPIs)
        $totalCount = $allVerifications->count();
        $conformeCount = $allVerifications->where('overall_status_key', 'conforme')->count();
        $nonConformeCount = $allVerifications->where('overall_status_key', 'non_conforme')->count();
        $enCoursCount = $allVerifications->where('overall_status_key', 'en_cours')->count();
        $conformanceRate = ($conformeCount + $nonConformeCount > 0)
            ? round(($conformeCount / ($conformeCount + $nonConformeCount)) * 100, 1)
            : 100.0;

        $transmitterCount = $allVerifications->where('verification_type', 'transmitter')->count();
        $probeCount = $allVerifications->where('verification_type', 'probe')->count();
        $fcCount = $allVerifications->where('verification_type', 'flow_computer')->count();

        // تجميع المعايرات حسب التقرير
        $groupedByReport = $allVerifications->groupBy(function ($v) {
            return $v['report']['id'] ?? 'unassigned';
        });

        $reportsQuery = Report::with(['mission.site']);
        if ($reportFilter) {
            $reportsQuery->where('id', $reportFilter);
        }
        if ($missionFilter) {
            $reportsQuery->where('mission_id', $missionFilter);
        }
        $reportModels = $reportsQuery->orderByDesc('id')->get()->keyBy('id');

        $reportGroups = collect();
        foreach ($reportModels as $repId => $reportModel) {
            $items = $groupedByReport->get($repId, collect());

            $reportTotal = $items->count();
            $reportConforme = $items->where('overall_status_key', 'conforme')->count();
            $reportNonConforme = $items->where('overall_status_key', 'non_conforme')->count();
            $reportEnCours = $items->where('overall_status_key', 'en_cours')->count();
            $reportRate = ($reportConforme + $reportNonConforme > 0)
                ? round(($reportConforme / ($reportConforme + $reportNonConforme)) * 100, 1)
                : 100.0;

            $activeInstrumentsCount = $reportModel->active_instruments_count;

            $reportGroups->push([
                'report_id' => $repId,
                'report_number' => $reportModel->report_number,
                'report_status' => $reportModel->status ?? 'completed',
                'mission_ref' => $reportModel->mission?->reference ?? '---',
                'site_name' => $reportModel->mission?->site?->full_name ?? $reportModel->mission?->site?->short_name ?? '---',
                'site_code' => $reportModel->mission?->site?->site_code ?? '',
                'site_short_name' => $reportModel->mission?->site?->short_name ?? '',
                'created_at' => $reportModel->created_at?->format('d/m/Y') ?? '---',
                'active_instruments_count' => $activeInstrumentsCount,
                'show_url' => route('metrology.reports.show', $repId),
                'pdf_url' => route('metrology.reports.pdf', $repId),
                'pdf_summary_url' => route('metrology.reports.pdf.summary', $repId),
                'edit_url' => Route::has('metrology.reports.edit') ? route('metrology.reports.edit', $repId) : null,
                'delete_url' => Route::has('metrology.reports.destroy') ? route('metrology.reports.destroy', $repId) : null,
                'stats' => [
                    'total' => $reportTotal,
                    'conforme' => $reportConforme,
                    'non_conforme' => $reportNonConforme,
                    'en_cours' => $reportEnCours,
                    'rate' => $reportRate,
                    'transmitters' => $items->where('verification_type', 'transmitter')->count(),
                    'probes' => $items->where('verification_type', 'probe')->count(),
                    'flow_computers' => $items->where('verification_type', 'flow_computer')->count(),
                ],
                'items' => $items->sortBy('instrument.tag')->values(),
            ]);
        }

        // إضافة المعايرات غير المخصصة لأي تقرير إن وُجدت
        if ($groupedByReport->has('unassigned') && $groupedByReport->get('unassigned')->isNotEmpty()) {
            $unassignedItems = $groupedByReport->get('unassigned');
            $reportGroups->push([
                'report_id' => 'unassigned',
                'report_number' => __('Rapport Non Assigné'),
                'report_status' => 'progress',
                'mission_ref' => '---',
                'site_name' => '---',
                'site_code' => '',
                'site_short_name' => '',
                'created_at' => '---',
                'active_instruments_count' => $unassignedItems->count(),
                'show_url' => null,
                'pdf_url' => null,
                'pdf_summary_url' => null,
                'edit_url' => null,
                'delete_url' => null,
                'stats' => [
                    'total' => $unassignedItems->count(),
                    'conforme' => $unassignedItems->where('overall_status_key', 'conforme')->count(),
                    'non_conforme' => $unassignedItems->where('overall_status_key', 'non_conforme')->count(),
                    'en_cours' => $unassignedItems->where('overall_status_key', 'en_cours')->count(),
                    'rate' => 100.0,
                    'transmitters' => $unassignedItems->where('verification_type', 'transmitter')->count(),
                    'probes' => $unassignedItems->where('verification_type', 'probe')->count(),
                    'flow_computers' => $unassignedItems->where('verification_type', 'flow_computer')->count(),
                ],
                'items' => $unassignedItems->sortBy('instrument.tag')->values(),
            ]);
        }

        // تجزئة الصفحات العامة (Pagination)
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $allVerifications->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $paginatedVerifications = new LengthAwarePaginator(
            $currentItems,
            $totalCount,
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $reportsQueryForSelect = Report::orderByDesc('id');
        if ($missionFilter) {
            $reportsQueryForSelect->where('mission_id', $missionFilter);
        }
        $reports = $reportsQueryForSelect->take(50)->get(['id', 'report_number', 'mission_id']);
        $missions = Mission::with('site')->orderByDesc('id')->get();

        $viewName = view()->exists('metrology.reports.report-instruments.index')
            ? 'metrology.reports.report-instruments.index'
            : 'admin.reports.report-instruments.index';

        return view($viewName, [
            'reportGroups' => $reportGroups,
            'verifications' => $paginatedVerifications,
            'stats' => [
                'total' => $totalCount,
                'conforme' => $conformeCount,
                'non_conforme' => $nonConformeCount,
                'en_cours' => $enCoursCount,
                'conformance_rate' => $conformanceRate,
                'transmitters' => $transmitterCount,
                'probes' => $probeCount,
                'flow_computers' => $fcCount,
            ],
            'filters' => [
                'search' => $search,
                'type' => $typeFilter,
                'status' => $statusFilter,
                'report_id' => $reportFilter,
                'mission_id' => $missionFilter,
                'per_page' => $perPage,
            ],
            'reports' => $reports,
            'missions' => $missions,
            'totalReportsCount' => Report::count(),
            'completedReportsCount' => Report::where('status', 'completed')->count(),
            'inProgressReportsCount' => Report::where('status', '!=', 'completed')->count(),
        ]);
    }

    /**
     * تحويل كائن المعايرة إلى صيغة موحدة لعرضها في الجدول
     */
    private function transformVerification(Model $v, string $type): array
    {
        $instrument = $v->instrument;
        $report = $v->report;
        $points = $v->points ?? collect();

        $specs = $instrument?->specifications?->first();
        $rangeStr = $specs ? ($specs->range_min.' - '.$specs->range_max.' '.($specs->grandeur?->symbol ?? '')) : '---';

        $maxError = null;
        $maxEmt = null;
        $calibratedPointsCount = 0;

        foreach ($points as $p) {
            $hasVal = match ($type) {
                'transmitter' => ($p->measured_signal !== null || $p->indicated_value !== null),
                'probe' => ($p->indicated_temperature !== null || $p->measured_resistance !== null),
                'flow_computer' => ($p->indicated_value !== null || $p->measured_signal !== null),
                default => false,
            };

            if ($hasVal) {
                $calibratedPointsCount++;
            }

            if ($p->absolute_error !== null) {
                $err = abs((float) $p->absolute_error);
                if ($maxError === null || $err > $maxError) {
                    $maxError = $err;
                }
            }

            if ($p->emt_limit !== null) {
                $emt = (float) $p->emt_limit;
                if ($maxEmt === null || $emt > $maxEmt) {
                    $maxEmt = $emt;
                }
            }
        }

        $typeLabel = match ($type) {
            'transmitter' => __('Transmitter'),
            'probe' => __('Probe Pt100'),
            'flow_computer' => __('Flow Computer'),
            default => ucfirst($type),
        };

        $statusKey = 'en_cours';
        if ($v->overall_status === true || $v->overall_status === 1) {
            $statusKey = 'conforme';
        } elseif ($v->overall_status === false || $v->overall_status === 0) {
            $statusKey = 'non_conforme';
        }

        return [
            'id' => $v->id,
            'verification_type' => $type,
            'type_label' => $typeLabel,
            'verification_date_raw' => $v->verification_date ? $v->verification_date->timestamp : ($v->created_at?->timestamp ?? 0),
            'verification_date' => $v->verification_date ? $v->verification_date->format('d/m/Y') : '---',
            'ambient_temperature' => $v->ambient_temperature,
            'ambient_pressure' => $v->ambient_pressure,
            'overall_status' => $v->overall_status,
            'overall_status_key' => $statusKey,
            'instrument' => [
                'id' => $instrument?->id,
                'tag' => $instrument?->tag_number ?? '---',
                'serial' => $instrument?->serial_number ?? '---',
                'type' => $instrument?->instrument_type instanceof \BackedEnum ? $instrument->instrument_type->value : (string) ($instrument?->instrument_type ?? ''),
                'process_variable' => $instrument?->process_variable instanceof \BackedEnum ? $instrument->process_variable->value : (string) ($instrument?->process_variable ?? ''),
                'technology' => $instrument?->technology ?? '---',
                'range' => $rangeStr,
                'image_url' => $instrument?->image_path ? asset('storage/'.$instrument->image_path) : null,
            ],
            'report' => [
                'id' => $report?->id,
                'number' => $report?->report_number ?? '---',
                'status' => $report?->status,
            ],
            'site' => $report?->mission?->site?->short_name ?? $instrument?->site?->short_name ?? '---',
            'mission' => $report?->mission?->reference ?? $report?->mission?->code ?? $report?->mission?->title ?? '---',
            'points_total' => $points->count(),
            'points_calibrated' => $calibratedPointsCount,
            'max_error' => $maxError !== null ? number_format($maxError, 4) : null,
            'max_emt' => $maxEmt !== null ? number_format($maxEmt, 4) : null,
            'calibrators_count' => $v->calibrators?->count() ?? 0,
            'curve_url' => ($report && $instrument) ? route('metrology.reports.curve', ['report' => $report->id, 'instrument' => $instrument->id]) : null,
            'saisie_url' => ($report && $instrument) ? route('metrology.reports.saisie', ['report' => $report->id, 'instrument' => $instrument->id]) : null,
            'report_url' => $report ? route('metrology.reports.show', $report->id) : null,
        ];
    }

    /**
     * نقطة التوجيه المركزية لواجهة إدخال القياسات (Router Dispatcher)
     */
    public function createSaisie(Report $report, Instrument $instrument): View|RedirectResponse
    {
        $instrument->load(['specifications.grandeur', 'standardGaugeSpecification']);

        $typeVal = strtolower($instrument->instrument_type instanceof \BackedEnum ? $instrument->instrument_type->value : (string) $instrument->instrument_type);

        // إلزامية المواصفات الفنية لمرسلات الضغط والحرارة ومجسات Pt100
        if (in_array($typeVal, ['transmitter', 'probe'], true) && $instrument->specifications->isEmpty()) {
            return redirect()->route('metrology.instruments.show', $instrument->id)
                ->with('error', __('Calibration cannot be started. Please enter the instrument technical specifications first to ensure metrological calculation accuracy.'));
        }

        return match ($typeVal) {
            'transmitter' => $this->createTransmitterSaisie($report, $instrument),
            'probe' => $this->createProbeSaisie($report, $instrument),
            'flowcomputer', 'flow_computer' => $this->createFlowComputerSaisie($report, $instrument),
            'chromatograph' => $this->redirectToChromatographSaisie($report, $instrument),
            'prover', 'testmeasure', 'standardgauge', 'standard_gauge' => redirect()->route('metrology.reports.report-prover.index'),
            default => abort(404, 'Interface de saisie non implémentée pour ce type d\'instrument.'),
        };
    }

    private function redirectToChromatographSaisie(Report $report, Instrument $instrument): RedirectResponse
    {
        $verification = ChromatographVerification::where('report_mission_id', $report->id)
            ->where('instrument_id', $instrument->id)
            ->first();

        if ($verification) {
            return redirect()->route('metrology.reports.report-chromatograph.saisie', $verification->id);
        }

        return redirect()->route('metrology.reports.report-chromatograph.create', [
            'report_id' => $report->id,
            'instrument_id' => $instrument->id,
        ]);
    }

    /* =========================================================================
       1. مرسلات الضغط والحرارة (Transmitters)
       ========================================================================= */

    private function createTransmitterSaisie(Report $report, Instrument $instrument): View
    {
        $specifications = $instrument->specifications->first();
        if (! $specifications) {
            $specifications = new InstrumentSpecification([
                'range_min' => 0.0,
                'range_max' => 100.0,
                'accuracy_value' => 0.5,
            ]);
        }

        $verification = TransmitterVerification::with('points', 'calibrators')
            ->where('report_mission_id', $report->id)
            ->where('instrument_id', $instrument->id)
            ->first();

        $calData = $this->calibratorService->resolveForTransmitter($report, $instrument, $verification);
        $defaultPercentages = [0.0, 25.0, 50.0, 75.0, 100.0, 100.0, 75.0, 50.0, 25.0, 0.0];

        $viewName = view()->exists('metrology.reports.report-instruments.transmitter')
            ? 'metrology.reports.report-instruments.transmitter'
            : 'admin.calibration_instruments.transmitter';

        return view($viewName, array_merge([
            'report' => $report,
            'instrument' => $instrument,
            'specifications' => $specifications,
            'verification' => $verification,
            'defaultPercentages' => $defaultPercentages,
        ], $calData));
    }

    public function storeTransmitterSaisie(StoreTransmitterRequest $request, Report $report, Instrument $instrument): RedirectResponse
    {
        $validated = $request->validated();

        $specs = $instrument->specifications->first();
        $span = (float) (($specs->range_max ?? 100) - ($specs->range_min ?? 0));
        $min = (float) ($specs->range_min ?? 0);
        $measurand = $specs?->grandeur?->name ?? 'Pression';
        $pressureType = $instrument->measurement_type ?? 'Relative';
        $fluidType = $instrument->fluid_type instanceof \BackedEnum ? $instrument->fluid_type->value : (string) ($instrument->fluid_type ?? 'Liquid');

        $cal1Id = ! empty($validated['calibrator_1']) ? (int) $validated['calibrator_1'] : null;
        $cal2Id = ! empty($validated['calibrator_2']) ? (int) $validated['calibrator_2'] : null;

        $prepPoints = fn (array $pts) => array_map(fn ($p) => [
            'nominal' => (float) ($p['nominal'] ?? 0),
            'value' => (float) ($p['value'] ?? $p['correction'] ?? 0),
            'correction' => (float) ($p['correction'] ?? $p['value'] ?? 0),
            'uncertainty' => (float) ($p['uncertainty'] ?? 0),
        ], $pts);

        $cal1Points = $cal1Id ? $prepPoints($this->calibratorService->getActiveCertificatePoints($cal1Id)) : [];
        $cal2Points = $cal2Id ? $prepPoints($this->calibratorService->getActiveCertificatePoints($cal2Id)) : [];

        DB::transaction(function () use ($validated, $report, $instrument, $span, $min, $measurand, $pressureType, $fluidType, $cal1Points, $cal2Points) {
            $verification = TransmitterVerification::updateOrCreate(
                ['report_mission_id' => $report->id, 'instrument_id' => $instrument->id],
                [
                    'verification_date' => $validated['verification_date'],
                    'ambient_temperature' => $validated['ambient_temperature'] ?? null,
                    'ambient_pressure' => $validated['ambient_pressure'] ?? null,
                    'overall_status' => true,
                ]
            );

            $verification->syncCalibratorByRole(
                ! empty($validated['calibrator_1']) ? (int) $validated['calibrator_1'] : null,
                1
            );
            $verification->syncCalibratorByRole(
                ! empty($validated['calibrator_2']) ? (int) $validated['calibrator_2'] : null,
                2
            );

            TransmitterVerificationPoint::where('verification_id', $verification->id)->delete();

            $pointsData = [];
            $ambientP = (float) ($validated['ambient_pressure'] ?? 0.0);
            $allConforme = true;

            foreach ($validated['points'] as $index => $point) {
                $rawRef = (float) $point['reference_value'];
                $rawSignal = isset($point['measured_signal']) && $point['measured_signal'] !== '' && $point['measured_signal'] !== null
                    ? (float) $point['measured_signal']
                    : null;

                // 1. استيفاء تصحيح المعيار 1 (مولد الضغط / الحرارة المرجعي)
                $cal1Correction = null;
                $correctedRef = $rawRef;
                if (! empty($cal1Points)) {
                    try {
                        $interp1 = $this->interpolationService->interpolate($cal1Points, $rawRef);
                        $cal1Correction = (float) $interp1['interpolated_value'];
                        $correctedRef = $rawRef + $cal1Correction;
                    } catch (\Throwable $e) {
                        $cal1Correction = isset($point['calibrator_1_correction']) && $point['calibrator_1_correction'] !== '' ? (float) $point['calibrator_1_correction'] : null;
                        $correctedRef = $cal1Correction !== null ? $rawRef + $cal1Correction : $rawRef;
                    }
                } elseif (isset($point['calibrator_1_correction']) && $point['calibrator_1_correction'] !== '') {
                    $cal1Correction = (float) $point['calibrator_1_correction'];
                    $correctedRef = $rawRef + $cal1Correction;
                }

                // 2. استيفاء تصحيح المعيار 2 (ملتيميتر قياس التيار 4-20 mA)
                $cal2Correction = null;
                $correctedSignal = $rawSignal;
                if ($rawSignal !== null && ! empty($cal2Points)) {
                    try {
                        $interp2 = $this->interpolationService->interpolate($cal2Points, $rawSignal);
                        $cal2Correction = (float) $interp2['interpolated_value'];
                        $correctedSignal = $rawSignal + $cal2Correction;
                    } catch (\Throwable $e) {
                        $cal2Correction = isset($point['calibrator_2_correction']) && $point['calibrator_2_correction'] !== '' ? (float) $point['calibrator_2_correction'] : null;
                        $correctedSignal = $cal2Correction !== null ? $rawSignal + $cal2Correction : $rawSignal;
                    }
                } elseif (isset($point['calibrator_2_correction']) && $point['calibrator_2_correction'] !== '') {
                    $cal2Correction = (float) $point['calibrator_2_correction'];
                    $correctedSignal = $rawSignal !== null ? $rawSignal + $cal2Correction : null;
                }

                // 3. التقييم المترولوجي باستخدام القيمة المرجعية المصححة والإشارة المصححة
                $eval = $this->oamService->evaluateTransmitter(
                    $span, $min, $correctedRef,
                    $correctedSignal,
                    isset($point['indicated_value']) ? (float) $point['indicated_value'] : null,
                    $fluidType, $instrument->technology,
                    $measurand, $pressureType, $ambientP
                );

                if (! $eval['is_conforme']) {
                    $allConforme = false;
                }

                $pointsData[] = [
                    'verification_id' => $verification->id,
                    'step_order' => $index + 1,
                    'cycle_phase' => ($index + 1 <= 5) ? 'Ascending' : 'Descending',
                    'applied_percentage' => (float) $point['applied_percentage'],
                    'reference_value' => $rawRef,
                    'calibrator_1_correction' => $cal1Correction,
                    'corrected_reference_value' => $correctedRef,
                    'measured_signal' => $rawSignal,
                    'calibrator_2_correction' => $cal2Correction,
                    'corrected_signal' => $correctedSignal,
                    'indicated_value' => $point['indicated_value'] ?? null,
                    'absolute_error' => $eval['error'],
                    'emt_limit' => $eval['emt'],
                    'is_conforme' => (int) $eval['is_conforme'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            TransmitterVerificationPoint::insert($pointsData);
            $verification->update(['overall_status' => $allConforme]);
        });

        $route = route('metrology.reports.saisie', ['report' => $report->id, 'instrument' => $instrument->id]);

        return redirect($route)->with('success', __('Données du transmetteur enregistrées avec succès.'));
    }

    /* =========================================================================
       2. المسابير الحرارية (Probes - Sondes)
       ========================================================================= */

    private function createProbeSaisie(Report $report, Instrument $instrument): View
    {
        $specifications = $instrument->specifications->first();

        $verification = ProbeVerification::with('points', 'calibrators')
            ->where('report_mission_id', $report->id)
            ->where('instrument_id', $instrument->id)
            ->first();

        $calData = $this->calibratorService->resolveForProbe($report, $instrument, $verification);
        $defaultPercentages = [0.0, 25.0, 50.0, 75.0, 100.0];

        $viewName = view()->exists('metrology.reports.report-instruments.probe')
            ? 'metrology.reports.report-instruments.probe'
            : 'admin.calibration_instruments.probe';

        return view($viewName, array_merge([
            'report' => $report,
            'instrument' => $instrument,
            'specifications' => $specifications,
            'verification' => $verification,
            'defaultPercentages' => $defaultPercentages,
        ], $calData));
    }

    public function storeProbeSaisie(StoreProbeRequest $request, Report $report, Instrument $instrument): RedirectResponse
    {
        $validated = $request->validated();

        $cal1Id = ! empty($validated['calibrator_1']) ? (int) $validated['calibrator_1'] : null;
        $cal2Id = ! empty($validated['calibrator_2']) ? (int) $validated['calibrator_2'] : null;

        $prepPoints = fn (array $pts) => array_map(fn ($p) => [
            'nominal' => (float) ($p['nominal'] ?? 0),
            'value' => (float) ($p['value'] ?? $p['correction'] ?? 0),
            'correction' => (float) ($p['correction'] ?? $p['value'] ?? 0),
            'uncertainty' => (float) ($p['uncertainty'] ?? 0),
        ], $pts);

        $cal1Points = $cal1Id ? $prepPoints($this->calibratorService->getActiveCertificatePoints($cal1Id)) : [];
        $cal2Points = $cal2Id ? $prepPoints($this->calibratorService->getActiveCertificatePoints($cal2Id)) : [];

        DB::transaction(function () use ($validated, $report, $instrument, $cal1Points, $cal2Points) {
            $verification = ProbeVerification::updateOrCreate(
                ['report_mission_id' => $report->id, 'instrument_id' => $instrument->id],
                [
                    'verification_date' => $validated['verification_date'],
                    'ambient_temperature' => $validated['ambient_temperature'] ?? null,
                    'ambient_pressure' => $validated['ambient_pressure'] ?? null,
                    'overall_status' => true,
                ]
            );

            $verification->syncCalibratorByRole(
                ! empty($validated['calibrator_1']) ? (int) $validated['calibrator_1'] : null,
                1
            );
            $verification->syncCalibratorByRole(
                ! empty($validated['calibrator_2']) ? (int) $validated['calibrator_2'] : null,
                2
            );

            ProbeVerificationPoint::where('verification_id', $verification->id)->delete();

            $pointsData = [];
            $allConforme = true;

            foreach ($validated['points'] as $index => $point) {
                $rawRef = (float) $point['reference_temperature'];
                $rawResistance = isset($point['measured_resistance']) && $point['measured_resistance'] !== '' ? (float) $point['measured_resistance'] : null;

                // 1. استيفاء وتصحيح القيمة المرجعية المطبقة (Calibrator 1)
                $cal1Correction = null;
                $correctedRef = $rawRef;
                if (! empty($cal1Points)) {
                    try {
                        $interp1 = $this->interpolationService->interpolate($cal1Points, $rawRef);
                        $cal1Correction = (float) $interp1['interpolated_value'];
                        $correctedRef = $rawRef + $cal1Correction;
                    } catch (\Throwable $e) {
                        $cal1Correction = isset($point['calibrator_1_correction']) && $point['calibrator_1_correction'] !== '' ? (float) $point['calibrator_1_correction'] : null;
                        $correctedRef = $cal1Correction !== null ? $rawRef + $cal1Correction : $rawRef;
                    }
                } elseif (isset($point['calibrator_1_correction']) && $point['calibrator_1_correction'] !== '') {
                    $cal1Correction = (float) $point['calibrator_1_correction'];
                    $correctedRef = $rawRef + $cal1Correction;
                }

                // 2. استيفاء وتصحيح المقاومة المقاسة (Calibrator 2)
                $cal2Correction = null;
                $correctedResistance = $rawResistance;
                if ($rawResistance !== null && ! empty($cal2Points)) {
                    try {
                        $interp2 = $this->interpolationService->interpolate($cal2Points, $rawResistance);
                        $cal2Correction = (float) $interp2['interpolated_value'];
                        $correctedResistance = $rawResistance + $cal2Correction;
                    } catch (\Throwable $e) {
                        $cal2Correction = isset($point['calibrator_2_correction']) && $point['calibrator_2_correction'] !== '' ? (float) $point['calibrator_2_correction'] : null;
                        $correctedResistance = $cal2Correction !== null ? $rawResistance + $cal2Correction : $rawResistance;
                    }
                } elseif (isset($point['calibrator_2_correction']) && $point['calibrator_2_correction'] !== '') {
                    $cal2Correction = (float) $point['calibrator_2_correction'];
                    $correctedResistance = $rawResistance !== null ? $rawResistance + $cal2Correction : null;
                }

                $eval = $this->oamService->evaluateProbePt100(
                    $correctedRef,
                    (float) $point['indicated_temperature']
                );

                if (! $eval['is_conforme']) {
                    $allConforme = false;
                }

                $pointsData[] = [
                    'verification_id' => $verification->id,
                    'step_order' => $index + 1,
                    'cycle_phase' => 'Ascending',
                    'reference_temperature' => $rawRef,
                    'calibrator_1_correction' => $cal1Correction,
                    'corrected_reference_temperature' => $correctedRef,
                    'measured_resistance' => $rawResistance,
                    'calibrator_2_correction' => $cal2Correction,
                    'corrected_measured_resistance' => $correctedResistance,
                    'indicated_temperature' => (float) $point['indicated_temperature'],
                    'absolute_error' => $eval['error'],
                    'emt_limit' => $eval['emt'],
                    'is_conforme' => (int) $eval['is_conforme'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            ProbeVerificationPoint::insert($pointsData);
            $verification->update(['overall_status' => $allConforme]);
        });

        $route = route('metrology.reports.saisie', ['report' => $report->id, 'instrument' => $instrument->id]);

        return redirect($route)->with('success', __('Points d\'étalonnage pour la sonde enregistrés avec succès.'));
    }

    /* =========================================================================
       3. حاسبات التدفق (Flow Computers / ADC)
       ========================================================================= */

    private function createFlowComputerSaisie(Report $report, Instrument $instrument): View
    {
        $specifications = $instrument->specifications->first();

        $linkedTransmitters = $instrument->linkedTransmitters()
            ->with(['specifications' => fn ($q) => $q->latest()->with('grandeur')])
            ->get();

        $activeTransmitterId = request('transmitter_id') ?? $linkedTransmitters->first()?->id;
        $activeTransmitter = $linkedTransmitters->firstWhere('id', (int) $activeTransmitterId);

        $activeGrandeur = $activeTransmitter?->specifications->first()?->grandeur?->name ?? 'Pression';
        $activeSymbol = $activeTransmitter?->specifications->first()?->grandeur?->symbol ?? 'bar';

        $allVerifications = FlowComputerVerification::with(['points', 'calibrators'])
            ->where('report_mission_id', $report->id)
            ->where('instrument_id', $instrument->id)
            ->get()
            ->keyBy('simulated_transmitter_id');

        $verification = $activeTransmitter ? $allVerifications->get($activeTransmitter->id) : null;

        $calData = $this->calibratorService->resolveForFlowComputer(
            $report,
            $instrument,
            $verification,
            $activeTransmitter
        );

        $channelData = null;
        if ($activeTransmitter && $activeTransmitter->specifications->isNotEmpty()) {
            $actSpec = $activeTransmitter->specifications->first();
            $tMin = (float) ($actSpec->range_min ?? 0);
            $tMax = (float) ($actSpec->range_max ?? 100);
            $tSpan = $tMax - $tMin;
            $isTemp = stripos($activeGrandeur, 'Temp') !== false || in_array(strtolower((string) $activeSymbol), ['°c', 'c', 'k']);
            $channelFluid = $activeTransmitter->fluid_type instanceof \BackedEnum ? $activeTransmitter->fluid_type->value : (string) ($activeTransmitter->fluid_type ?? 'Liquid');

            $dummyEval = $this->oamService->evaluateADC($tSpan, $tMin, 4.0, 250.0, $tMin, $channelFluid, $activeGrandeur);
            $isRelativeEmt = ($dummyEval['error_type'] ?? 'Absolute') === 'Relative';

            $channelData = [
                'grandeur' => $activeGrandeur,
                'symbol' => $activeSymbol,
                'min' => $tMin,
                'max' => $tMax,
                'span' => $tSpan,
                'is_temp' => $isTemp,
                'is_relative_emt' => $isRelativeEmt,
                'emt_approved' => $dummyEval['emt'] ?? ($actSpec->accuracy_value ?? 0.1),
                'emt_display' => $isTemp ? '°C' : ($isRelativeEmt ? '%' : $activeSymbol),
                'shunt_resistance' => old('shunt_resistance', $verification->shunt_resistance ?? 250.00),
                'verification_date' => old('verification_date', $verification?->verification_date?->format('Y-m-d') ?? date('Y-m-d')),
            ];
        }

        $viewName = view()->exists('metrology.reports.report-instruments.flow_computer')
            ? 'metrology.reports.report-instruments.flow_computer'
            : 'admin.calibration_instruments.flow_computer';

        return view($viewName, array_merge([
            'report' => $report,
            'instrument' => $instrument,
            'specifications' => $specifications,
            'verification' => $verification,
            'linkedTransmitters' => $linkedTransmitters,
            'activeTransmitter' => $activeTransmitter,
            'allVerifications' => $allVerifications,
            'channelData' => $channelData,
            'defaultPercentages' => [0.0, 25.0, 50.0, 75.0, 100.0, 100.0, 75.0, 50.0, 25.0, 0.0],
        ], $calData));
    }

    public function storeFlowComputerSaisie(StoreFlowComputerRequest $request, Report $report, Instrument $instrument): RedirectResponse
    {
        $validated = $request->validated();

        $cal1Id = ! empty($validated['calibrator_1']) ? (int) $validated['calibrator_1'] : null;
        $cal2Id = ! empty($validated['calibrator_2']) ? (int) $validated['calibrator_2'] : null;

        $prepPoints = fn (array $pts) => array_map(fn ($p) => [
            'nominal' => (float) ($p['nominal'] ?? 0),
            'value' => (float) ($p['value'] ?? $p['correction'] ?? 0),
            'correction' => (float) ($p['correction'] ?? $p['value'] ?? 0),
            'uncertainty' => (float) ($p['uncertainty'] ?? 0),
        ], $pts);

        $cal1Points = $cal1Id ? $prepPoints($this->calibratorService->getActiveCertificatePoints($cal1Id)) : [];
        $cal2Points = $cal2Id ? $prepPoints($this->calibratorService->getActiveCertificatePoints($cal2Id)) : [];

        DB::transaction(function () use ($validated, $report, $instrument, $cal1Points, $cal2Points) {
            $verification = FlowComputerVerification::updateOrCreate(
                [
                    'report_mission_id' => $report->id,
                    'instrument_id' => $instrument->id,
                    'simulated_transmitter_id' => $validated['transmitter_id'],
                ],
                [
                    'shunt_resistance' => $validated['shunt_resistance'] ?? 250.00,
                    'verification_date' => $validated['verification_date'],
                    'ambient_temperature' => $validated['ambient_temperature'] ?? null,
                    'ambient_pressure' => $validated['ambient_pressure'] ?? null,
                    'overall_status' => true,
                ]
            );

            $verification->syncCalibratorByRole(
                ! empty($validated['calibrator_1']) ? (int) $validated['calibrator_1'] : null,
                1
            );
            $verification->syncCalibratorByRole(
                ! empty($validated['calibrator_2']) ? (int) $validated['calibrator_2'] : null,
                2
            );

            FlowComputerVerificationPoint::where('verification_id', $verification->id)->delete();

            $pointsData = [];
            $simTransmitter = Instrument::with('specifications.grandeur')->find($validated['transmitter_id']);
            $simSpecs = $simTransmitter?->specifications->first();
            $tMin = (float) ($simSpecs->range_min ?? 0);
            $tMax = (float) ($simSpecs->range_max ?? 100);
            $tSpan = $tMax - $tMin;
            $measurand = $simSpecs?->grandeur?->name ?? 'Pression';
            $channelFluid = $simTransmitter->fluid_type instanceof \BackedEnum ? $simTransmitter->fluid_type->value : (string) ($simTransmitter->fluid_type ?? 'Liquid');
            $rShunt = (float) ($validated['shunt_resistance'] ?? 250.00);
            $allConforme = true;

            foreach ($validated['points'] as $index => $point) {
                $rawSignal = (float) $point['measured_signal'];
                $rawExpectedValue = (float) $point['expected_value'];

                // 1. استيفاء وتصحيح إشارة التيار المحقونة (Calibrator 1)
                $cal1Correction = null;
                $correctedSignal = $rawSignal;
                if (! empty($cal1Points)) {
                    try {
                        $interp1 = $this->interpolationService->interpolate($cal1Points, $rawSignal);
                        $cal1Correction = (float) $interp1['interpolated_value'];
                        $correctedSignal = $rawSignal + $cal1Correction;
                    } catch (\Throwable $e) {
                        $cal1Correction = isset($point['calibrator_1_correction']) && $point['calibrator_1_correction'] !== '' ? (float) $point['calibrator_1_correction'] : null;
                        $correctedSignal = $cal1Correction !== null ? $rawSignal + $cal1Correction : $rawSignal;
                    }
                } elseif (isset($point['calibrator_1_correction']) && $point['calibrator_1_correction'] !== '') {
                    $cal1Correction = (float) $point['calibrator_1_correction'];
                    $correctedSignal = $rawSignal + $cal1Correction;
                }

                // 2. استيفاء وتصحيح القيمة الفيزيائية المتوقعة (Calibrator 2)
                $cal2Correction = null;
                $correctedExpectedValue = $rawExpectedValue;
                if (! empty($cal2Points)) {
                    try {
                        $interp2 = $this->interpolationService->interpolate($cal2Points, $rawExpectedValue);
                        $cal2Correction = (float) $interp2['interpolated_value'];
                        $correctedExpectedValue = $rawExpectedValue + $cal2Correction;
                    } catch (\Throwable $e) {
                        $cal2Correction = isset($point['calibrator_2_correction']) && $point['calibrator_2_correction'] !== '' ? (float) $point['calibrator_2_correction'] : null;
                        $correctedExpectedValue = $cal2Correction !== null ? $rawExpectedValue + $cal2Correction : $rawExpectedValue;
                    }
                } elseif (isset($point['calibrator_2_correction']) && $point['calibrator_2_correction'] !== '') {
                    $cal2Correction = (float) $point['calibrator_2_correction'];
                    $correctedExpectedValue = $rawExpectedValue + $cal2Correction;
                }

                // التقييم باستخدام الإشارة المصححة
                $eval = $this->oamService->evaluateADC(
                    $tSpan, $tMin,
                    $correctedSignal,
                    $rShunt,
                    (float) $point['indicated_value'],
                    $channelFluid,
                    $measurand
                );

                if (! $eval['is_conforme']) {
                    $allConforme = false;
                }

                $pointsData[] = [
                    'verification_id' => $verification->id,
                    'step_order' => $index + 1,
                    'cycle_phase' => ($index + 1 <= 5) ? 'Ascending' : 'Descending',
                    'applied_percentage' => (float) $point['applied_percentage'],
                    'expected_signal' => (float) $point['expected_signal'],
                    'measured_signal' => $rawSignal,
                    'calibrator_1_correction' => $cal1Correction,
                    'corrected_signal' => $correctedSignal,
                    'expected_value' => $rawExpectedValue,
                    'calibrator_2_correction' => $cal2Correction,
                    'corrected_expected_value' => $correctedExpectedValue,
                    'indicated_value' => (float) $point['indicated_value'],
                    'absolute_error' => $eval['error'],
                    'emt_limit' => $eval['emt'],
                    'is_conforme' => (int) $eval['is_conforme'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            FlowComputerVerificationPoint::insert($pointsData);
            $verification->update(['overall_status' => $allConforme]);
        });

        $route = route('metrology.reports.saisie', [
            'report' => $report->id,
            'instrument' => $instrument->id,
            'transmitter_id' => $validated['transmitter_id'],
        ]);

        return redirect($route)->with('success', __('Points d\'étalonnage pour le calculateur enregistrés avec succès.'));
    }

    /* =========================================================================
       4. منحنيات المعايرة والخطأ المترولوجي (SVG Calibration Curves)
       ========================================================================= */

    public function showCurve(Report $report, Instrument $instrument): View
    {
        $instrument->load(['specifications.grandeur', 'standardGaugeSpecification']);
        $specs = $instrument->specifications->first();

        $curves = [];
        $title = 'Courbe d\'Étalonnage';
        $typeVal = strtolower($instrument->instrument_type instanceof \BackedEnum ? $instrument->instrument_type->value : (string) $instrument->instrument_type);

        if ($typeVal === 'transmitter') {
            $verification = TransmitterVerification::with('points')
                ->where('report_mission_id', $report->id)
                ->where('instrument_id', $instrument->id)
                ->firstOrFail();

            $points = $verification->points;
            $rangeMin = (float) ($specs->range_min ?? 0);
            $rangeMax = (float) ($specs->range_max ?? 100);
            $emt = (float) ($points->first()?->emt_limit ?? 0);
            $title = 'Courbe d\'Erreur - Transmetteur: '.$instrument->tag_number;
            $maxErr = $points->max(fn ($p) => abs((float) ($p->absolute_error ?? 0.0))) ?? 0.0;

            $curves[] = [
                'label' => $instrument->tag_number,
                'svg' => $this->chartService->generateErrorCurve($points, $emt, $rangeMin, $rangeMax, 960, 530),
                'emt' => $emt,
                'conformity' => ($maxErr <= abs($emt)) ? 'Conforme' : 'Non Conforme',
            ];

        } elseif ($typeVal === 'probe') {
            $verification = ProbeVerification::with('points')
                ->where('report_mission_id', $report->id)
                ->where('instrument_id', $instrument->id)
                ->firstOrFail();

            $points = $verification->points;
            $rangeMin = (float) ($specs->range_min ?? -50);
            $rangeMax = (float) ($specs->range_max ?? 200);
            $emt = (float) ($points->max('emt_limit') ?? 0.15);
            $title = 'Courbe d\'Erreur - Sonde PT100: '.$instrument->tag_number;
            $maxErr = $points->max(fn ($p) => abs((float) ($p->absolute_error ?? 0.0))) ?? 0.0;

            $curves[] = [
                'label' => $instrument->tag_number,
                'svg' => $this->chartService->generateErrorCurve($points, $emt, $rangeMin, $rangeMax, 960, 530),
                'emt' => $emt,
                'conformity' => ($maxErr <= abs($emt)) ? 'Conforme' : 'Non Conforme',
            ];

        } elseif ($typeVal === 'flowcomputer' || $typeVal === 'flow_computer') {
            $title = 'Courbes ADC - FC: '.$instrument->tag_number;

            $linkedTransmitters = $instrument->linkedTransmitters()
                ->with('specifications.grandeur')
                ->get();

            abort_if($linkedTransmitters->isEmpty(), 404, 'Aucune voie associée à ce calculateur de débit.');

            foreach ($linkedTransmitters as $simTransmitter) {
                $verification = FlowComputerVerification::with('points')
                    ->where('report_mission_id', $report->id)
                    ->where('instrument_id', $instrument->id)
                    ->where('simulated_transmitter_id', $simTransmitter->id)
                    ->first();

                if (! $verification || $verification->points->isEmpty()) {
                    $curves[] = [
                        'label' => $simTransmitter->tag_number,
                        'svg' => null,
                        'emt' => 0.0,
                        'conformity' => null,
                    ];

                    continue;
                }

                $simSpecs = $simTransmitter->specifications->first();
                $points = $verification->points;
                $rangeMin = (float) ($simSpecs->range_min ?? 0);
                $rangeMax = (float) ($simSpecs->range_max ?? 100);
                $emt = (float) ($points->first()?->emt_limit ?? 0);
                $maxErr = $points->max(fn ($p) => abs((float) ($p->absolute_error ?? 0.0))) ?? 0.0;

                $curves[] = [
                    'label' => $simTransmitter->tag_number,
                    'svg' => $this->chartService->generateErrorCurve($points, $emt, $rangeMin, $rangeMax, 960, 530),
                    'emt' => $emt,
                    'conformity' => ($maxErr <= abs($emt)) ? 'Conforme' : 'Non Conforme',
                ];
            }
        }

        $firstCurve = $curves[0] ?? [];
        $svgCurve = $firstCurve['svg'] ?? '';
        $emt = $firstCurve['emt'] ?? 0.0;
        $conformity = $firstCurve['conformity'] ?? null;

        return view('metrology.reports.curve', compact(
            'report', 'instrument', 'title',
            'curves', 'svgCurve', 'emt', 'conformity'
        ));
    }
}
