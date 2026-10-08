<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccuracyType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'instrument_id',
    'grandeur_id',
    'range_min',
    'range_max',
    'accuracy_value',
    'accuracy_type',
])]
class InstrumentSpecification extends Model
{
    use HasFactory;

    protected $table = 'instrument_specifications';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'range_min' => 'float',
            'range_max' => 'float',
            'accuracy_value' => 'float',
            'accuracy_type' => AccuracyType::class,
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
     * The physical quantity parameter / standard unit.
     */
    public function grandeur(): BelongsTo
    {
        return $this->belongsTo(Grandeur::class, 'grandeur_id');
    }
}
