<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'instrument_id',
    'nominal_capacity_liters',
    'neck_scale_sensitivity',
    'cubical_expansion_coef_gcm',
    'vessel_material',
    'base_reference_temperature',
    'calibration_certificate_number',
    'calibration_date',
    'calibration_expiry_date',
])]
class StandardGaugeSpecification extends Model
{
    use HasFactory;

    protected $table = 'standard_gauge_specifications';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nominal_capacity_liters' => 'float',
            'neck_scale_sensitivity' => 'float',
            'cubical_expansion_coef_gcm' => 'float',
            'base_reference_temperature' => 'float',
            'calibration_date' => 'date',
            'calibration_expiry_date' => 'date',
        ];
    }

    /**
     * The parent industrial instrument.
     */
    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class, 'instrument_id');
    }

    /**
     * Alias accessor for base reference temperature in celsius.
     */
    public function getReferenceTemperatureCelsiusAttribute(): ?float
    {
        return $this->base_reference_temperature !== null
            ? (float) $this->base_reference_temperature
            : null;
    }
}
