<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExpenseAffiliation;
use App\Enums\ExpenseChargeType;
use App\Traits\FilterableTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'amount',
    'date',
    'description',
    'type',
    'charge_type',
    'mission_id',
    'contract_id',
    'attachment_item_id',
    'item_type_id',
])]
class Expense extends Model
{
    use FilterableTrait, HasActivity, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'charges';

    /**
     * The attributes that are allowed for dynamic query filtering.
     *
     * @var list<string>
     */
    protected array $filterable = [
        'id',
        'amount',
        'date',
        'type',
        'charge_type',
        'mission_id',
        'contract_id',
        'attachment_item_id',
        'created_at',
    ];

    /**
     * The attributes that are searched via the keyword 'search' / 'q' filter.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'description',
    ];

    /**
     * Cast attributes to native types.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'type' => ExpenseAffiliation::class,
            'charge_type' => ExpenseChargeType::class,
        ];
    }

    /**
     * Activity log configuration.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'amount',
                'date',
                'description',
                'type',
                'charge_type',
                'mission_id',
                'contract_id',
                'attachment_item_id',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('expenses')
            ->setDescriptionForEvent(fn (string $eventName): string => "Expense #{$this->id} has been {$eventName}");
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * The mission linked to this expense (nullable).
     *
     * @return BelongsTo<Mission, $this>
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
    }

    /**
     * The contract linked to this expense (nullable).
     *
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    /**
     * The contract line item type linked to this expense (nullable).
     *
     * @return BelongsTo<ItemType, $this>
     */
    public function itemType(): BelongsTo
    {
        return $this->belongsTo(ItemType::class, 'item_type_id');
    }

    /**
     * The specific attachment execution item linked to this expense (nullable).
     *
     * @return BelongsTo<AttachmentItem, $this>
     */
    public function attachmentItem(): BelongsTo
    {
        return $this->belongsTo(AttachmentItem::class, 'attachment_item_id');
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Filter by annual calendar year or all.
     */
    public function scopeForYear(Builder $query, ?string $year): Builder
    {
        if (! $year || $year === 'all') {
            return $query;
        }

        return $query->whereYear('date', $year);
    }

    /**
     * Filter by expense affiliation type.
     */
    public function scopeForAffiliation(Builder $query, ?string $type): Builder
    {
        if (! $type || $type === 'all') {
            return $query;
        }

        return $query->where('type', $type);
    }

    /**
     * Filter by fixed/variable charge type when affiliation is GMTM.
     */
    public function scopeForChargeType(Builder $query, ?string $chargeType): Builder
    {
        if (! $chargeType || $chargeType === 'all') {
            return $query;
        }

        return $query->where('charge_type', $chargeType);
    }
}
