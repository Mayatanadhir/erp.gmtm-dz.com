<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FilterableTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'designation',
])]
class ItemType extends Model
{
    use FilterableTrait, HasActivity, HasFactory;

    protected $table = 'item_types';

    protected array $filterable = [
        'id',
        'designation',
        'created_at',
    ];

    protected array $searchable = [
        'designation',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['designation'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('item_types')
            ->setDescriptionForEvent(fn (string $eventName): string => "Item type {$this->designation} {$eventName}");
    }

    /**
     * Contract line items linked to this item type.
     *
     * @return HasMany<ContractItem, $this>
     */
    public function contractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'item_type_id');
    }
}
