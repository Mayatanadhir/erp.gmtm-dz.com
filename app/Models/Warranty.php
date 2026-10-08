<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WarrantyStatus;
use App\Enums\WarrantyType;
use App\Traits\FilterableTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'reference',
    'bank_name',
    'amount',
    'started_at',
    'status',
    'type',
])]
class Warranty extends Model
{
    use FilterableTrait, HasActivity, HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'garanties';

    /**
     * The attributes that are allowed for dynamic query filtering.
     *
     * @var list<string>
     */
    protected array $filterable = [
        'id',
        'reference',
        'bank_name',
        'status',
        'type',
        'started_at',
        'created_at',
    ];

    /**
     * The attributes that are searched via the keyword 'search' / 'q' filter.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'reference',
        'bank_name',
    ];

    /**
     * Cast attributes to native types.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => WarrantyStatus::class,
            'type' => WarrantyType::class,
            'amount' => 'decimal:2',
            'started_at' => 'date',
        ];
    }

    /**
     * Get the activity log options for the Warranty model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'reference',
                'bank_name',
                'amount',
                'started_at',
                'status',
                'type',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "Bank guarantee has been {$eventName}");
    }

    /**
     * Formatted amount with currency.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 2, '.', ' ').' DZD';
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Contracts that use this warranty as a bank guarantee.
     *
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'garantie_id');
    }

    // ==========================================
    // Model Events
    // ==========================================

    /**
     * When a Warranty is deleted, nullify garantie_id on all linked contracts.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $warranty): void {
            $warranty->contracts()->update(['garantie_id' => null]);
        });
    }
}
