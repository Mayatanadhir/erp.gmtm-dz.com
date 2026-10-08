<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowComputerVerificationPoint extends Model
{
    use HasFactory;

    protected $table = 'flow_computer_verification_points';

    protected $fillable = [
        'verification_id',
        'step_order',
        'cycle_phase',
        'applied_percentage',
        'expected_signal',
        'measured_signal',
        'expected_value',
        'indicated_value',
        'absolute_error',
        'emt_limit',
        'is_conforme',
    ];

    protected $casts = [
        'applied_percentage' => 'float',
        'expected_signal' => 'float',
        'measured_signal' => 'float',
        'expected_value' => 'float',
        'indicated_value' => 'float',
        'absolute_error' => 'float',
        'emt_limit' => 'float',
        'is_conforme' => 'boolean',
    ];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(FlowComputerVerification::class, 'verification_id');
    }
}
