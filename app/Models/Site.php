<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FilterableTrait;
use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'customer_id',
    'site_code',
    'full_name',
    'short_name',
    'location',
    'map_link',
])]
class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use FilterableTrait, HasActivity, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sites';

    /**
     * The attributes that are allowed for dynamic query filtering.
     *
     * @var list<string>
     */
    protected array $filterable = [
        'id',
        'customer_id',
        'site_code',
        'full_name',
        'short_name',
        'location',
        'created_at',
    ];

    /**
     * The attributes that are searched via the keyword 'search' / 'q' filter.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'site_code',
        'full_name',
        'short_name',
        'location',
    ];

    /**
     * Customer relationship.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the activity log options for the Site model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'customer_id',
                'site_code',
                'full_name',
                'short_name',
                'location',
                'map_link',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "Site has been {$eventName}");
    }

    /**
     * Generate initials from the site name or code.
     */
    public function getInitialsAttribute(): string
    {
        $name = trim((string) ($this->short_name ?: $this->full_name ?: $this->site_code));
        $words = preg_split('/\s+/', $name);

        if (empty($words) || ! is_array($words) || blank($words[0])) {
            return 'ST';
        }

        if (count($words) === 1) {
            return mb_strtoupper(mb_substr($words[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr(end($words), 0, 1));
    }

    /**
     * Display name helper.
     */
    public function getDisplayNameAttribute(): string
    {
        return (string) ($this->full_name ?: $this->short_name ?: $this->site_code ?: ('Site #'.$this->id));
    }

    /**
     * Field missions executed on this industrial site.
     *
     * @return HasMany<Mission, $this>
     */
    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class, 'site_id');
    }

    /**
     * Measuring instruments installed on this site.
     *
     * @return HasMany<Instrument, $this>
     */
    public function instruments(): HasMany
    {
        return $this->hasMany(Instrument::class, 'site_id');
    }
}
