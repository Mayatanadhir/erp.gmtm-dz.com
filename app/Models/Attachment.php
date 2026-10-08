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
    'mission_id',
    'date',
    'ods',
    'code_ref',
    'status',
    'type',
    'frequency',
])]
class Attachment extends Model
{
    use FilterableTrait, HasActivity, HasFactory;

    protected $table = 'attachments';

    protected array $filterable = [
        'id',
        'mission_id',
        'code_ref',
        'status',
        'type',
        'frequency',
    ];

    protected array $searchable = [
        'code_ref',
        'ods',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'frequency' => BillingCycle::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['mission_id', 'code_ref', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('attachments')
            ->setDescriptionForEvent(fn (string $eventName): string => "Attachment {$this->code_ref} {$eventName}");
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AttachmentItem::class, 'attachment_id');
    }

    /**
     * Total calculated revenue amount for this attachment.
     */
    public function getTotalAmountAttribute(): float
    {
        return (float) $this->items->sum(function (AttachmentItem $item): float {
            return (float) ($item->actual_quantity ?? 0) * (float) ($item->contractItem?->unit_price ?? 0);
        });
    }

    /**
     * Total planned revenue amount for this attachment.
     */
    public function getTotalPlannedAmountAttribute(): float
    {
        return (float) $this->items->sum(function (AttachmentItem $item): float {
            return (float) ($item->planned_quantity ?? 0) * (float) ($item->contractItem?->unit_price ?? 0);
        });
    }

    /**
     * Total actual quantity across all line items.
     */
    public function getTotalActualQuantityAttribute(): float
    {
        return (float) $this->items->sum('actual_quantity');
    }

    /**
     * Total planned quantity across all line items.
     */
    public function getTotalPlannedQuantityAttribute(): float
    {
        return (float) $this->items->sum('planned_quantity');
    }
}
