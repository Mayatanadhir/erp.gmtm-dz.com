<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FilterableTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'attachment_id',
    'contract_item_id',
    'actual_quantity',
    'planned_quantity',
])]
class AttachmentItem extends Model
{
    use FilterableTrait, HasFactory, SoftDeletes;

    protected $table = 'attachment_items';

    protected array $filterable = [
        'id',
        'attachment_id',
        'contract_item_id',
    ];

    protected function casts(): array
    {
        return [
            'actual_quantity' => 'decimal:2',
            'planned_quantity' => 'decimal:2',
        ];
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'attachment_id');
    }

    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class, 'contract_item_id');
    }

    /**
     * Total amount for this line item (actual_quantity * unit_price).
     */
    public function getTotalAttribute(): float
    {
        return (float) ($this->actual_quantity ?? 0) * (float) ($this->contractItem?->unit_price ?? 0);
    }

    /**
     * Planned total amount for this line item.
     */
    public function getPlannedTotalAttribute(): float
    {
        return (float) ($this->planned_quantity ?? 0) * (float) ($this->contractItem?->unit_price ?? 0);
    }

    /**
     * Charges linked to this attachment line item.
     *
     * @return HasMany<Expense, $this>
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Expense::class, 'attachment_item_id');
    }
}
