<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EquipmentCategory;
use App\Enums\EquipmentPackage;
use App\Enums\EquipmentStatus;
use App\Observers\EquipmentObserver;
use App\Traits\FilterableTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'internal_code',
    'full_name',
    'short_name',
    'serial_number',
    'category',
    'package',
    'requires_calibration',
    'designation',
    'status',
    'image_path',
    'image_hash',
    'certificate_path',
    'notes',
])]
#[ObservedBy([EquipmentObserver::class])]
class Equipment extends Model
{
    use FilterableTrait, HasActivity, HasFactory, SoftDeletes;

    protected $table = 'equipment';

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'image_url',
    ];

    /**
     * The attributes that are allowed for dynamic query filtering.
     *
     * @var list<string>
     */
    protected array $filterable = [
        'id',
        'internal_code',
        'full_name',
        'short_name',
        'serial_number',
        'category',
        'package',
        'requires_calibration',
        'status',
        'created_at',
    ];

    /**
     * The attributes that are searched via the keyword 'search' / 'q' filter.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'full_name',
        'short_name',
        'internal_code',
        'serial_number',
        'designation',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => EquipmentCategory::class,
            'status' => EquipmentStatus::class,
            'package' => EquipmentPackage::class,
            'requires_calibration' => 'boolean',
        ];
    }

    /**
     * Get the display name of the equipment.
     */
    public function getNameAttribute(): string
    {
        return $this->short_name ?? $this->full_name ?? $this->internal_code ?? ('Equipment #'.$this->id);
    }

    /**
     * Get URL for equipment image or null.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->image_path)) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        return asset('storage/'.ltrim($this->image_path, '/'));
    }

    /**
     * Get URL for latest certificate PDF or null.
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
     * Physical parameters and specifications configured for this equipment.
     */
    public function specifications(): HasMany
    {
        return $this->hasMany(EquipmentSpecification::class, 'equipment_id');
    }

    /**
     * Grandeurs linked to this equipment.
     */
    public function grandeurs(): BelongsToMany
    {
        return $this->belongsToMany(Grandeur::class, 'equipment_specifications', 'equipment_id', 'grandeur_id');
    }

    /**
     * Calibration certificates registered for this equipment.
     */
    public function calibrationCertificates(): HasMany
    {
        return $this->hasMany(CalibrationCertificate::class, 'equipment_id');
    }

    /**
     * Latest calibration certificate registered for this equipment.
     *
     * @return HasOne<CalibrationCertificate, $this>
     */
    public function latestCertificate(): HasOne
    {
        return $this->hasOne(CalibrationCertificate::class, 'equipment_id')->latestOfMany(['calibration_date', 'id']);
    }

    /**
     * Scope a query to only include equipment eligible for metrological calibration.
     * Strictly excludes work tools, vehicles, and items not accepting calibration.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCalibration(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('requires_calibration', true)
                ->orWhere('category', EquipmentCategory::MeasuringInstrument->value);
        })->whereNotIn('category', [
            EquipmentCategory::WorkTool->value,
            EquipmentCategory::Vehicle->value,
        ]);
    }

    /**
     * Determine if this equipment accepts or requires metrological calibration.
     */
    public function isCalibrationEligible(): bool
    {
        $isNotExcluded = ! in_array($this->category, [EquipmentCategory::WorkTool, EquipmentCategory::Vehicle], true);
        $requiresOrMeasuring = (bool) $this->requires_calibration || $this->category === EquipmentCategory::MeasuringInstrument;

        return $isNotExcluded && $requiresOrMeasuring;
    }

    /**
     * Spatie Activity Log Options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'internal_code',
                'full_name',
                'short_name',
                'serial_number',
                'category',
                'package',
                'requires_calibration',
                'status',
                'designation',
                'image_path',
                'certificate_path',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('equipment');
    }
}
