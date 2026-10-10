<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FilterableTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'reference',
    'object',
    'date_signature',
    'duree',
    'montant_global_prevu',
    'customer_id',
    'garantie_id',
])]
class Contract extends Model
{
    use FilterableTrait, HasActivity, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'contracts';

    /**
     * The attributes that are allowed for dynamic query filtering.
     *
     * @var list<string>
     */
    protected array $filterable = [
        'id',
        'reference',
        'object',
        'customer_id',
        'garantie_id',
        'date_signature',
        'created_at',
    ];

    /**
     * The attributes that are searched via the keyword 'search' / 'q' filter.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'reference',
        'object',
    ];

    /**
     * Cast attributes to native types.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'date_signature' => 'date',
            'montant_global_prevu' => 'decimal:2',
            'duree' => 'integer',
        ];
    }

    /**
     * Activity log configuration.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'reference',
                'object',
                'date_signature',
                'duree',
                'montant_global_prevu',
                'customer_id',
                'garantie_id',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('contracts')
            ->setDescriptionForEvent(fn (string $eventName): string => "Contract {$this->reference} has been {$eventName}");
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Scope to return only active (non-expired) contracts.
     */
    public function scopeActive(Builder $query): Builder
    {
        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return $query->whereNotNull('date_signature')
                ->whereNotNull('duree')
                ->whereRaw(
                    "date(date_signature, '+' || duree || ' months') >= ?",
                    [now()->startOfDay()->toDateString()]
                );
        }

        if ($driver === 'pgsql') {
            return $query->whereNotNull('date_signature')
                ->whereNotNull('duree')
                ->whereRaw(
                    "(date_signature + (duree || ' month')::interval) >= ?",
                    [now()->startOfDay()]
                );
        }

        return $query->whereNotNull('date_signature')
            ->whereNotNull('duree')
            ->whereRaw(
                'DATE_ADD(date_signature, INTERVAL duree MONTH) >= ?',
                [now()->startOfDay()]
            );
    }

    /**
     * Determine if the contract is active (not expired).
     */
    public function isActive(): bool
    {
        return $this->remain_days !== null && $this->remain_days >= 0;
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * The customer who signed this contract.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * The bank guarantee linked to this contract (nullable).
     *
     * @return BelongsTo<Warranty, $this>
     */
    public function warranty(): BelongsTo
    {
        return $this->belongsTo(Warranty::class, 'garantie_id');
    }

    /**
     * Alias for warranty relation.
     *
     * @return BelongsTo<Warranty, $this>
     */
    public function garantie(): BelongsTo
    {
        return $this->warranty();
    }

    /**
     * Contract line items.
     *
     * @return HasMany<ContractItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'contract_id');
    }

    /**
     * Field missions under this contract.
     *
     * @return HasMany<Mission, $this>
     */
    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class, 'contract_id');
    }

    /**
     * Attachments (via Mission → Attachment).
     *
     * @return HasManyThrough<Attachment, Mission, $this>
     */
    public function attachments(): HasManyThrough
    {
        return $this->hasManyThrough(Attachment::class, Mission::class);
    }

    /**
     * Expenses / Charges assigned directly to this contract.
     *
     * @return HasMany<Expense, $this>
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Expense::class, 'contract_id');
    }

    // ==========================================
    // Accessors
    // ==========================================

    /**
     * Number of remaining days until contract expiry.
     * Returns null if date_signature or duree is missing.
     */
    public function getRemainDaysAttribute(): ?int
    {
        if (! $this->date_signature || ! $this->duree) {
            return null;
        }

        $endDate = $this->date_signature->copy()->addMonthsNoOverflow((int) $this->duree);

        return (int) now()->startOfDay()->diffInDays($endDate, false);
    }

    /**
     * Percentage of remaining contract time (0–100).
     */
    public function getPercentRemainingAttribute(): float
    {
        if (! $this->date_signature || ! $this->duree) {
            return 0.0;
        }

        $endDate = $this->date_signature->copy()->addMonthsNoOverflow((int) $this->duree);
        $totalDays = (int) $this->date_signature->diffInDays($endDate);
        $remainDays = $this->remain_days ?? 0;

        if ($totalDays <= 0 || $remainDays < 0) {
            return 0.0;
        }

        return round(($remainDays / $totalDays) * 100, 2);
    }

    /**
     * Contract phase: 'unknown' | 'start' | 'mid' | 'end' | 'archive'
     */
    public function getExpiryStatusAttribute(): string
    {
        $remain = $this->remain_days;
        $percent = $this->percent_remaining;

        if ($remain === null) {
            return 'unknown';
        }

        if ($remain < 0) {
            return 'archive';
        }

        if ($percent > 66) {
            return 'start';
        }

        if ($percent > 33) {
            return 'mid';
        }

        return 'end';
    }

    /**
     * Semantic badge variant for the expiry status.
     */
    public function expiryBadgeVariant(): string
    {
        return match ($this->expiry_status) {
            'start' => 'success',
            'mid' => 'info',
            'end' => 'warning',
            'archive' => 'neutral',
            default => 'neutral',
        };
    }

    /**
     * Translated phase label for the expiry status.
     */
    public function expiryPhaseLabel(): string
    {
        return match ($this->expiry_status) {
            'start' => __('Start Phase'),
            'mid' => __('Mid Phase'),
            'end' => __('Near Expiry'),
            'archive' => __('Expired / Archived'),
            default => __('Active'),
        };
    }

    /**
     * CSS badge class based on expiry status.
     */
    public function getExpiryBadgeClassAttribute(): string
    {
        return match ($this->expiry_status) {
            'start' => 'badge-glass-success',
            'mid' => 'badge-glass-info',
            'end' => 'badge-glass-warning',
            'archive' => 'badge-glass-neutral',
            default => 'badge-glass-neutral',
        };
    }

    // ==========================================
    // Financial Methods
    // ==========================================

    /**
     * Sum of (unit_price × quantity) for all contract items.
     */
    public function totalPlanned(): string
    {
        $total = $this->items->sum(fn (ContractItem $item) => (float) $item->unit_price * (int) $item->quantity);

        return $this->formatCurrency($total);
    }

    /**
     * Sum of (unit_price × quantity) for service items.
     */
    public function servicesTotalPlanned(): string
    {
        $total = $this->items
            ->filter(fn (ContractItem $item) => ($item->type ?? 'service') === 'service')
            ->sum(fn (ContractItem $item) => (float) $item->unit_price * (int) $item->quantity);

        return $this->formatCurrency((float) $total);
    }

    /**
     * Sum of (unit_price × quantity) for supply items.
     */
    public function suppliesTotalPlanned(): string
    {
        $total = $this->items
            ->where('type', 'supply')
            ->sum(fn (ContractItem $item) => (float) $item->unit_price * (int) $item->quantity);

        return $this->formatCurrency((float) $total);
    }

    /**
     * Count of service items.
     */
    public function servicesCount(): int
    {
        return $this->items->filter(fn (ContractItem $item) => ($item->type ?? 'service') === 'service')->count();
    }

    /**
     * Count of supply items.
     */
    public function suppliesCount(): int
    {
        return $this->items->where('type', 'supply')->count();
    }

    /**
     * Sum of (actual_quantity × unit_price) for APPROVED attachments only.
     */
    public function totalInvoiced(): string
    {
        if (! Schema::hasTable('attachment_items') || ! Schema::hasTable('attachments')) {
            return $this->formatCurrency(0.0);
        }

        $itemIds = $this->items()->pluck('id');

        $total = DB::table('attachment_items')
            ->join('contract_items', 'attachment_items.contract_item_id', '=', 'contract_items.id')
            ->join('attachments', 'attachment_items.attachment_id', '=', 'attachments.id')
            ->whereIn('attachment_items.contract_item_id', $itemIds)
            ->where('attachments.status', 'approved')
            ->sum(DB::raw('attachment_items.actual_quantity * contract_items.unit_price'));

        return $this->formatCurrency((float) $total);
    }

    /**
     * Sum of (actual_quantity × unit_price) across ALL attachment statuses.
     */
    public function totalConsumed(): string
    {
        if (! Schema::hasTable('attachment_items')) {
            return $this->formatCurrency(0.0);
        }

        $itemIds = $this->items()->pluck('id');

        $total = DB::table('attachment_items')
            ->join('contract_items', 'attachment_items.contract_item_id', '=', 'contract_items.id')
            ->whereIn('attachment_items.contract_item_id', $itemIds)
            ->sum(DB::raw('attachment_items.actual_quantity * contract_items.unit_price'));

        return $this->formatCurrency((float) $total);
    }

    /**
     * Unconsumed planned amount: Items sum - Total Consumed.
     */
    public function totalUnconsumed(): string
    {
        return $this->formatCurrency($this->rawTotalUnconsumed());
    }

    /**
     * Numeric unconsumed planned amount.
     */
    public function rawTotalUnconsumed(): float
    {
        $rawPlanned = (float) $this->items->sum(fn (ContractItem $item) => (float) $item->unit_price * (int) $item->quantity);

        if (! Schema::hasTable('attachment_items')) {
            return $rawPlanned;
        }

        $itemIds = $this->relationLoaded('items')
            ? $this->items->pluck('id')
            : $this->items()->pluck('id');

        if ($itemIds->isEmpty()) {
            return $rawPlanned;
        }

        $rawConsumed = (float) DB::table('attachment_items')
            ->join('contract_items', 'attachment_items.contract_item_id', '=', 'contract_items.id')
            ->whereIn('attachment_items.contract_item_id', $itemIds)
            ->sum(DB::raw('attachment_items.actual_quantity * contract_items.unit_price'));

        return $rawPlanned - $rawConsumed;
    }

    /**
     * Count of attachments linked to this contract's items.
     */
    public function attachmentsCount(): int
    {
        if (! Schema::hasTable('attachments') || ! Schema::hasTable('attachment_items')) {
            return 0;
        }

        return Attachment::whereHas(
            'items',
            fn ($q) => $q->whereIn('contract_item_id', $this->items()->pluck('id'))
        )->count();
    }

    /**
     * Format an amount as "1 234 567.89 DA".
     */
    public function formatCurrency(float $amount): string
    {
        return number_format($amount, 2, '.', ' ').' DA';
    }
}
