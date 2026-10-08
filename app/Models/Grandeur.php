<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GrandeurDiscipline;
use App\Enums\GrandeurType;
use App\Traits\FilterableTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['name', 'symbol', 'type'])]
class Grandeur extends Model
{
    use FilterableTrait, HasActivity, HasFactory;

    protected $table = 'grandeurs';

    /**
     * The attributes that are allowed for dynamic query filtering.
     *
     * @var list<string>
     */
    protected array $filterable = [
        'id',
        'name',
        'symbol',
        'type',
        'created_at',
    ];

    /**
     * The attributes that are searched via the keyword 'search' / 'q' filter.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'name',
        'symbol',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => GrandeurType::class,
        ];
    }

    /**
     * Equipment specifications configured under this quantity unit.
     */
    public function specifications(): HasMany
    {
        return $this->hasMany(EquipmentSpecification::class, 'grandeur_id');
    }

    /**
     * Get the physical discipline / standard category.
     */
    public function discipline(): GrandeurDiscipline
    {
        return GrandeurDiscipline::detect($this->name, $this->symbol);
    }

    /**
     * Spatie Activity Log Options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'symbol', 'type'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('quantities_units');
    }
}
