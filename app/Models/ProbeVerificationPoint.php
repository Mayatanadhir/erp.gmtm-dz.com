<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProbeVerificationPoint extends Model
{
    use HasFactory;

    protected $table = 'probe_verification_points';

    protected $fillable = [
        'verification_id',
        'step_order',
        'cycle_phase',
        'reference_temperature',
        'measured_resistance',
        'indicated_temperature',
        'absolute_error',
        'emt_limit',
        'is_conforme',
    ];

    protected $casts = [
        'reference_temperature' => 'float',
        'measured_resistance' => 'float',
        'indicated_temperature' => 'float',
        'absolute_error' => 'float',
        'emt_limit' => 'float',
        'is_conforme' => 'boolean',
    ];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(ProbeVerification::class, 'verification_id');
    }
}
