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
        'measured_signal',
        'indicated_value',
        'absolute_error',
        'emt_limit',
        'is_conforme',
    ];

    protected $casts = [
        'applied_percentage' => 'float',
        'reference_value' => 'float',
        'measured_signal' => 'float',
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

        if ($this->measured_signal !== null && $span !== 0.0) {
            return ((float) $this->measured_signal - 4.0) / 16.0 * $span + $min;
        }

        return (float) ($this->reference_value ?? 0);
    }
}
