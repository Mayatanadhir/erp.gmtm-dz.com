<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'calibration_certificate_id',
    'equipment_specification_id',
    'point_index',
    'target_nominal',
    'interpolated_value',
    'experimental_uncertainty',
    'curvature_coefficient',
    'modeling_uncertainty',
    'combined_uncertainty',
    'expanded_uncertainty',
    'is_exact_point',
])]
class CalibrationInterpolation extends Model
{
    use HasActivity, HasFactory;

    protected $table = 'calibration_interpolations';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'equipment_specification_id' => 'integer',
            'point_index' => 'integer',
            'target_nominal' => 'float',
            'interpolated_value' => 'float',
            'experimental_uncertainty' => 'float',
            'curvature_coefficient' => 'float',
            'modeling_uncertainty' => 'float',
            'combined_uncertainty' => 'float',
            'expanded_uncertainty' => 'float',
            'is_exact_point' => 'boolean',
        ];
    }

    /**
     * Lower confidence limit: Y - U.
     */
    public function getLowerLimitAttribute(): float
    {
        return (float) ($this->interpolated_value - $this->expanded_uncertainty);
    }

    /**
     * Upper confidence limit: Y + U.
     */
    public function getUpperLimitAttribute(): float
    {
        return (float) ($this->interpolated_value + $this->expanded_uncertainty);
    }

    /**
     * Parent calibration certificate relation.
     */
    public function calibrationCertificate(): BelongsTo
    {
        return $this->belongsTo(CalibrationCertificate::class, 'calibration_certificate_id');
    }

    /**
     * Related equipment specification (standard parameter).
     */
    public function equipmentSpecification(): BelongsTo
    {
        return $this->belongsTo(EquipmentSpecification::class, 'equipment_specification_id');
    }

    /**
     * Spatie Activity Log configuration.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'calibration_certificate_id',
                'equipment_specification_id',
                'point_index',
                'target_nominal',
                'interpolated_value',
                'experimental_uncertainty',
                'curvature_coefficient',
                'modeling_uncertainty',
                'combined_uncertainty',
                'expanded_uncertainty',
                'is_exact_point',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('calibration_interpolation');
    }
}
