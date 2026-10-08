<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProverType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'instrument_id',
    'type',
    'inner_diameter',
    'wall_thickness',
    'nominal_base_volume',
    'cubical_expansion_coef',
    'elasticity_modulus',
    'area_expansion_coef',
    'linear_expansion_coef',
    'material',
    'pulse_interpolation',
])]
class ProverSpecification extends Model
{
    use HasFactory;

    protected $table = 'prover_specifications';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProverType::class,
            'inner_diameter' => 'float',
            'wall_thickness' => 'float',
            'nominal_base_volume' => 'float',
            'cubical_expansion_coef' => 'float',
            'elasticity_modulus' => 'float',
            'area_expansion_coef' => 'float',
            'linear_expansion_coef' => 'float',
            'pulse_interpolation' => 'boolean',
        ];
    }

    /**
     * The parent industrial instrument.
     */
    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class, 'instrument_id');
    }
}
