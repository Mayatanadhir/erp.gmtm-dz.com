<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProverVerification extends Model
{
    use HasFactory;

    protected $table = 'prover_verifications';

    protected $fillable = [
        'calibration_date',
        'reference_number',
        'reference_temperature',
        'pressure_unit',
        'remarks',
        'prover_id',
        'jauge_id',
        'base_prover_volume',
        'max_run_volume',
        'min_run_volume',
        'repeatability_percent',
        'is_conforme',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'calibration_date' => 'date',
            'reference_temperature' => 'float',
            'base_prover_volume' => 'float',
            'max_run_volume' => 'float',
            'min_run_volume' => 'float',
            'repeatability_percent' => 'float',
            'is_conforme' => 'boolean',
        ];
    }

    /**
     * Prover instrument under calibration.
     */
    public function prover(): BelongsTo
    {
        return $this->belongsTo(Instrument::class, 'prover_id');
    }

    /**
     * Standard volumetric gauge (jauge étalon) used as reference.
     */
    public function jauge(): BelongsTo
    {
        return $this->belongsTo(Instrument::class, 'jauge_id');
    }
}
