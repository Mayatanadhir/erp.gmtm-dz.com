<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransmitterVerificationPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'verification_id',
        'step_order',
        'cycle_phase',
        'applied_percentage',
        'reference_value',
        'calibrator_1_correction',
        'corrected_reference_value',
        'measured_signal',
        'calibrator_2_correction',
        'corrected_signal',
        'indicated_value',
        'absolute_error',
        'emt_limit',
        'is_conforme',
    ];

    protected $casts = [
        'applied_percentage' => 'float',
        'reference_value' => 'float',
        'calibrator_1_correction' => 'float',
        'corrected_reference_value' => 'float',
        'measured_signal' => 'float',
        'calibrator_2_correction' => 'float',
        'corrected_signal' => 'float',
        'indicated_value' => 'float',
        'absolute_error' => 'float',
        'emt_limit' => 'float',
        'is_conforme' => 'boolean',
    ];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(TransmitterVerification::class, 'verification_id');
    }

    /**
     * حساب القيمة الفيزيائية المكافئة (Valeur Calculée / Mesurée)
     */
    public function getCalculatedValueAttribute(): float
    {
        $specs = $this->verification?->instrument?->specifications?->first();
        $min = (float) ($specs->range_min ?? 0);
        $max = (float) ($specs->range_max ?? 100);
        $span = (float) ($max - $min);
        $tech = $this->verification?->instrument?->technology ?? 'Traditional';
        $fluid = $this->verification?->instrument?->fluid_type?->value ?? (string) ($this->verification?->instrument?->fluid_type ?? 'Liquid');

        if ((strcasecmp($fluid, 'Gas') === 0 || strcasecmp($tech, 'SMART') === 0) && $this->indicated_value !== null && $this->indicated_value !== '') {
            return (float) $this->indicated_value;
        }

        $signal = $this->corrected_signal ?? $this->measured_signal;
        if ($signal !== null && $span !== 0.0) {
            return ((float) $signal - 4.0) / 16.0 * $span + $min;
        }

        return (float) ($this->corrected_reference_value ?? $this->reference_value ?? 0);
    }
}
