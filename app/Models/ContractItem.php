<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingCycle;
use App\Traits\FilterableTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'contract_id',
    'item_type_id',
    'designation',
    'quantity',
    'unit_price',
    'type',
    'unit_cost',
    'frequency',
])]
class ContractItem extends Model
{
    use FilterableTrait, HasActivity, HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'contract_items';

    /**
     * The attributes that are allowed for dynamic query filtering.
     *
     * @var list<string>
     */
    protected array $filterable = [
        'id',
        'contract_id',
        'item_type_id',
        'type',
        'frequency',
    ];

    /**
     * The attributes that are searched via the keyword 'search' / 'q' filter.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'designation',
    ];

    /**
     * Cast attributes to native types.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'frequency' => BillingCycle::class,
        ];
    }

    /**
     * Activity log configuration.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'contract_id',
                'item_type_id',
                'designation',
                'quantity',
                'unit_price',
                'type',
                'unit_cost',
                'frequency',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('contract_items')
            ->setDescriptionForEvent(fn (string $eventName): string => "Contract item {$this->designation} has been {$eventName}");
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * The parent contract.
     *
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    /**
     * The item type from the master catalogue.
     *
     * @return BelongsTo<ItemType, $this>
     */
    public function itemType(): BelongsTo
    {
        return $this->belongsTo(ItemType::class, 'item_type_id');
    }

    /**
     * Actual consumption records per billing period.
     *
     * @return HasMany<AttachmentItem, $this>
     */
    public function attachmentItems(): HasMany
    {
        return $this->hasMany(AttachmentItem::class, 'contract_item_id');
    }

    // ==========================================
    // Accessors / Calculated Fields
    // ==========================================

    /**
     * Total revenue value: unit_price × quantity.
     */
    public function getTotalAttribute(): float
    {
        return (float) $this->unit_price * (int) $this->quantity;
    }

    /**
     * Total purchase cost: unit_cost × quantity.
     */
    public function getTotalPurchaseCostAttribute(): float
    {
        return (float) $this->unit_cost * (int) $this->quantity;
    }

    /**
     * Consumption percentage: (actual_qty / planned_qty) × 100.
     */
    public function getConsumptionPercentageAttribute(): float
    {
        $actual = (float) ($this->attachment_items_sum_quantity ?? $this->attachmentItems()->sum('actual_quantity'));
        $planned = (int) $this->quantity;

        if ($planned <= 0) {
            return 0.0;
        }

        return round(($actual / $planned) * 100, 2);
    }

    /**
     * CSS class for the consumption progress bar.
     */
    public function getConsumptionStatusClassAttribute(): string
    {
        $pct = $this->consumption_percentage;

        if ($pct >= 100) {
            return 'bg-danger';
        }

        if ($pct >= 75) {
            return 'bg-warning';
        }

        return 'bg-success';
    }
}
