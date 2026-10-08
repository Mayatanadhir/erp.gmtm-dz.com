<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CalibrationCertificateStatus;
use App\Traits\FilterableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'reference',
    'certificate_type',
    'equipment_id',
    'laboratory_name',
    'calibration_date',
    'expiry_date',
    'validity_period_months',
    'price',
    'is_locked',
    'status',
    'certificate_path',
    'certificate_hash',
    'file_name',
    'file_size',
    'mime_type',
    'environmental_conditions',
    'remarks',
    'previous_certificate_id',
    'created_by',
    'updated_by',
    'approved_by',
    'approved_at',
    'locked_at',
    'locked_by',
])]
class CalibrationCertificate extends Model
{
    use FilterableTrait, HasActivity, HasFactory, SoftDeletes;

    protected $table = 'calibration_certificates';

    /**
     * The attributes that are allowed for dynamic query filtering.
     *
     * @var list<string>
     */
    protected array $filterable = [
        'id',
        'reference',
        'certificate_type',
        'equipment_id',
        'laboratory_name',
        'calibration_date',
        'expiry_date',
        'is_locked',
        'status',
        'created_at',
    ];

    /**
     * Searchable attributes via keyword filter.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'reference',
        'laboratory_name',
        'remarks',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'calibration_date' => 'date',
            'expiry_date' => 'date',
            'validity_period_months' => 'integer',
            'is_locked' => 'boolean',
            'price' => 'float',
            'file_size' => 'integer',
            'environmental_conditions' => 'array',
            'status' => CalibrationCertificateStatus::class,
            'approved_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    /**
     * The equipment linked to this certificate.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    /**
     * Metrological calibration points measured for this certificate.
     */
    public function calibrationPoints(): HasMany
    {
        return $this->hasMany(CalibrationPoint::class, 'calibration_certificate_id');
    }

    /**
     * Standard 5-point interpolated grid points.
     */
    public function calibrationInterpolations(): HasMany
    {
        return $this->hasMany(CalibrationInterpolation::class, 'calibration_certificate_id')->orderBy('point_index', 'asc');
    }

    /**
     * Predecessor certificate for historical traceability.
     */
    public function previousCertificate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_certificate_id');
    }

    /**
     * Subsequent replacement certificates.
     */
    public function subsequentCertificates(): HasMany
    {
        return $this->hasMany(self::class, 'previous_certificate_id');
    }

    /**
     * User who created the certificate.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated the certificate.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Technical/Quality manager who approved the certificate.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * User who locked the certificate.
     */
    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * Direct URL for downloading the certificate PDF.
     */
    public function getCertificateUrlAttribute(): ?string
    {
        if (blank($this->certificate_path)) {
            return null;
        }

        if (str_starts_with($this->certificate_path, 'http://') || str_starts_with($this->certificate_path, 'https://')) {
            return $this->certificate_path;
        }

        return asset('storage/'.ltrim($this->certificate_path, '/'));
    }

    /**
     * Remaining days until expiration relative to today.
     */
    public function getRemainingDaysAttribute(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return (int) Carbon::now()->startOfDay()->diffInDays($this->expiry_date->startOfDay(), false);
    }

    /**
     * Check if the certificate is nearing its expiration (<= 30 days).
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        $remaining = $this->remaining_days;

        return $remaining !== null && $remaining >= 0 && $remaining <= 30;
    }

    /**
     * Check if the certificate is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        $remaining = $this->remaining_days;

        return $remaining !== null && $remaining < 0;
    }

    /**
     * Scope query to valid / operational certificates.
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->whereIn('status', [
            CalibrationCertificateStatus::Approved,
            CalibrationCertificateStatus::ExpiringSoon,
        ])->where(function (Builder $q): void {
            $q->whereNull('expiry_date')
                ->orWhereDate('expiry_date', '>=', Carbon::today());
        });
    }

    /**
     * Scope query to certificates expiring soon (within 30 days).
     */
    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereDate('expiry_date', '>=', Carbon::today())
            ->whereDate('expiry_date', '<=', Carbon::today()->addDays($days));
    }

    /**
     * Scope query to expired certificates.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('status', CalibrationCertificateStatus::Expired)
                ->orWhereDate('expiry_date', '<', Carbon::today());
        });
    }

    /**
     * Spatie Activity Log Options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'reference',
                'certificate_type',
                'equipment_id',
                'laboratory_name',
                'calibration_date',
                'expiry_date',
                'price',
                'is_locked',
                'status',
                'certificate_path',
                'remarks',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('calibration_certificate');
    }
}
