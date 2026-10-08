<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmployeePosition;
use App\Enums\EmployeeStatus;
use App\Observers\EmployeeObserver;
use App\Traits\FilterableTrait;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['user_id', 'full_name', 'registration_number', 'position', 'status', 'join_date', 'salary', 'daily_rate', 'address', 'profile_photo_path', 'photo_hash'])]
#[ObservedBy([EmployeeObserver::class])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use FilterableTrait, HasActivity, HasFactory, SoftDeletes;

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'profile_photo_url',
        'initials',
    ];

    /**
     * The attributes that are allowed for dynamic query filtering.
     *
     * @var list<string>
     */
    protected array $filterable = [
        'id',
        'user_id',
        'full_name',
        'registration_number',
        'position',
        'status',
        'join_date',
        'address',
        'created_at',
    ];

    /**
     * The attributes that are searched via the keyword 'search' / 'q' filter.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'full_name',
        'registration_number',
        'address',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'position' => EmployeePosition::class,
            'status' => EmployeeStatus::class,
            'salary' => 'decimal:2',
            'daily_rate' => 'decimal:2',
        ];
    }

    /**
     * Get the activity log options for the Employee model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'full_name',
                'registration_number',
                'position',
                'status',
                'join_date',
                'salary',
                'daily_rate',
                'address',
                'user_id',
                'profile_photo_path',
                'photo_hash',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "Employee has been {$eventName}");
    }

    /**
     * Get the linked user account.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the public URL for the employee profile photo.
     * Falls back to the linked user's profile photo if not set directly.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        $path = $this->profile_photo_path ?: $this->user?->profile_photo_path;

        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    /**
     * Generate initials from the employee full name.
     */
    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->full_name));

        if (empty($words) || ! is_array($words) || blank($words[0])) {
            return 'EM';
        }

        if (count($words) === 1) {
            return mb_strtoupper(mb_substr($words[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr(end($words), 0, 1));
    }
}
