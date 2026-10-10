<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProverVerificationRun extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'prover_verification_runs';

    protected $fillable = [
        'prover_verification_id',
        'run_number',
        'fill_number',
        'scale_reading_mm',
        'indicated_volume',
        'gauge_temperature',
        'prover_temperature',
        'shaft_temperature',
        'prover_pressure',
        'c_tdw',
        'c_tsm',
        'c_tsp',
        'c_psp',
        'c_plp',
        'corrected_volume',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'run_number' => 'integer',
            'fill_number' => 'integer',
            'scale_reading_mm' => 'float',
            'indicated_volume' => 'float',
            'gauge_temperature' => 'float',
            'prover_temperature' => 'float',
            'shaft_temperature' => 'float',
            'prover_pressure' => 'float',
            'c_tdw' => 'float',
            'c_tsm' => 'float',
            'c_tsp' => 'float',
            'c_psp' => 'float',
            'c_plp' => 'float',
            'corrected_volume' => 'float',
        ];
    }

    /**
     * Parent verification session.
     */
    public function verification(): BelongsTo
    {
        return $this->belongsTo(ProverVerification::class, 'prover_verification_id');
    }
}
