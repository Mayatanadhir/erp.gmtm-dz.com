<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChromatographVerification extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chromatograph_verifications';

    protected $appends = [
        'reference_number',
    ];

    protected $fillable = [
        'reference_number',
        'report_mission_id',
        'instrument_id',
        'verification_date',
        'ambient_temperature',
        'ambient_pressure',
        'standard_gas_bottle_number',
        'certificate_number',
        'reference_conditions',
        'cylinder_validity_date',
        'cylinder_pressure_bar',
        'repeatability_status',
        'composition_accuracy_status',
        'physical_properties_status',
        'overall_status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'verification_date' => 'date',
            'cylinder_validity_date' => 'date',
            'ambient_temperature' => 'float',
            'ambient_pressure' => 'float',
            'cylinder_pressure_bar' => 'float',
            'repeatability_status' => 'boolean',
            'composition_accuracy_status' => 'boolean',
            'physical_properties_status' => 'boolean',
            'overall_status' => 'boolean',
        ];
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class, 'instrument_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_mission_id');
    }

    public function compositionPoints(): HasMany
    {
        return $this->hasMany(ChromatographCompositionPoint::class, 'verification_id')->orderBy('step_order');
    }

    public function physicalProperties(): HasMany
    {
        return $this->hasMany(ChromatographPhysicalProperty::class, 'verification_id')
            ->orderByRaw("CASE property_symbol WHEN 'PCS' THEN 1 WHEN 'PCI' THEN 2 WHEN 'Pb' THEN 3 WHEN 'rho' THEN 3 WHEN 'Zb' THEN 4 WHEN 'Z' THEN 4 ELSE 99 END");
    }

    public function calibrators(): BelongsToMany
    {
        return $this->belongsToMany(
            Equipment::class,
            'chromatograph_verification_calibrators',
            'verification_id',
            'calibrator_id'
        )->withPivot('role');
    }

    /**
     * مرجعية جلسة الفحص / التقرير المترولوجي.
     */
    public function getReferenceNumberAttribute(): string
    {
        return $this->attributes['reference_number']
            ?? ($this->report?->report_number ?? sprintf('VERIF-GC-%05d', $this->id));
    }

    /**
     * توليد مرجعية جديدة تلقائياً لجلسة فحص الكروماتوغراف.
     * الصيغة: VERIF-GC-YYYY-NNNN
     */
    public static function generateReferenceNumber(): string
    {
        $year = now()->year;
        $prefix = "VERIF-GC-{$year}-";

        $last = static::query()
            ->where('reference_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $next = 1;
        if ($last && ! empty($last->reference_number)) {
            $parts = explode('-', $last->reference_number);
            $next = ((int) end($parts)) + 1;
        }

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
