<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccuracyType;
use App\Enums\GrandeurDiscipline;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'equipment_id',
    'grandeur_id',
    'range_min',
    'range_max',
    'accuracy_value',
    'accuracy_type',
])]
class EquipmentSpecification extends Model
{
    use HasActivity, HasFactory;

    protected $table = 'equipment_specifications';

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
     * The parent equipment.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    /**
     * The related quantity unit.
     */
    public function grandeur(): BelongsTo
    {
        return $this->belongsTo(Grandeur::class, 'grandeur_id');
    }

    /**
     * Get the physical discipline / standard category for this specification.
     */
    public function discipline(): GrandeurDiscipline
    {
        return $this->grandeur?->discipline() ?? GrandeurDiscipline::Generic;
    }

    /**
     * Spatie Activity Log Options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['equipment_id', 'grandeur_id', 'range_min', 'range_max', 'accuracy_value', 'accuracy_type'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('equipment_specifications');
    }
}
