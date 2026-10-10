<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FilterableTrait;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'reference',
    'company_name',
    'short_name',
    'address',
    'phone',
    'email',
    'website',
    'registration_number',
    'notes',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use FilterableTrait, HasActivity, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customers';

    /**
     * The attributes that are allowed for dynamic query filtering.
     *
     * @var list<string>
     */
    protected array $filterable = [
        'id',
        'reference',
        'company_name',
        'short_name',
        'address',
        'phone',
        'email',
        'website',
        'registration_number',
        'notes',
        'created_at',
    ];

    /**
     * The attributes that are searched via the keyword 'search' / 'q' filter.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'company_name',
        'short_name',
        'reference',
        'registration_number',
        'email',
        'phone',
        'address',
    ];

    /**
     * Get the activity log options for the Customer model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'reference',
                'company_name',
                'short_name',
                'address',
                'phone',
                'email',
                'website',
                'registration_number',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "Customer has been {$eventName}");
    }

    /**
     * Generate initials from the company name or short name.
     */
    public function getInitialsAttribute(): string
    {
        $name = trim((string) ($this->short_name ?: $this->company_name));
        $words = preg_split('/\s+/', $name);

        if (empty($words) || ! is_array($words) || blank($words[0])) {
            return 'CL';
        }

        if (count($words) === 1) {
            return mb_strtoupper(mb_substr($words[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr(end($words), 0, 1));
    }

    /**
     * Get the sites associated with the customer.
     *
     * @return HasMany<Site, $this>
     */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class, 'customer_id');
    }
}
