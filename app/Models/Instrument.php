<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FluidType;
use App\Enums\InstrumentStatus;
use App\Enums\InstrumentType;
use App\Enums\ProcessVariable;
use App\Observers\InstrumentObserver;
use App\Traits\FilterableTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'site_id',
    'tag_number',
    'serial_number',
    'instrument_type',
    'process_variable',
    'measurement_type',
    'fluid_type',
    'technology',
    'image_path',
    'image_hash',
    'status',
])]
#[ObservedBy([InstrumentObserver::class])]
class Instrument extends Model
{
    use FilterableTrait, HasActivity, HasFactory, SoftDeletes;

    protected $table = 'instruments';

    /**
     * The attributes that are allowed for dynamic query filtering.
     *
     * @var list<string>
     */
    protected array $filterable = [
        'id',
        'site_id',
        'tag_number',
        'serial_number',
        'instrument_type',
        'process_variable',
        'measurement_type',
        'fluid_type',
        'technology',
        'status',
        'created_at',
    ];

    /**
     * The attributes that are searched via the keyword 'search' / 'q' filter.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'tag_number',
        'serial_number',
        'technology',
        'measurement_type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'site_id' => 'integer',
            'instrument_type' => InstrumentType::class,
            'status' => InstrumentStatus::class,
            'process_variable' => ProcessVariable::class,
            'fluid_type' => FluidType::class,
        ];
    }

    /**
     * Get URL for instrument image or null.
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
     * Spatie activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'tag_number',
                'serial_number',
                'instrument_type',
                'status',
                'site_id',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('instruments')
            ->setDescriptionForEvent(fn (string $eventName): string => "Measuring Instrument {$eventName}");
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Industrial site where instrument is deployed.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    /**
     * Current or latest metrological specification.
     */
    public function specification(): HasOne
    {
        return $this->hasOne(InstrumentSpecification::class, 'instrument_id')->latestOfMany();
    }

    /**
     * All metrological specifications associated with the instrument.
     */
    public function specifications(): HasMany
    {
        return $this->hasMany(InstrumentSpecification::class, 'instrument_id');
    }

    /**
     * Grandeurs linked via specifications.
     */
    public function grandeurs(): BelongsToMany
    {
        return $this->belongsToMany(Grandeur::class, 'instrument_specifications', 'instrument_id', 'grandeur_id');
    }

    /**
     * Transmitters linked to this Flow Computer via channels.
     */
    public function linkedTransmitters(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'flow_computer_transmitter', 'flow_computer_id', 'transmitter_id')
            ->withPivot('channel_number')
            ->withTimestamps();
    }

    /**
     * Flow computers that this Transmitter is linked to.
     */
    public function flowComputers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'flow_computer_transmitter', 'transmitter_id', 'flow_computer_id')
            ->withPivot('channel_number')
            ->withTimestamps();
    }

    /**
     * Metrological specifications for standard gauge (Jauge étalon).
     */
    public function standardGaugeSpecification(): HasOne
    {
        return $this->hasOne(StandardGaugeSpecification::class, 'instrument_id');
    }

    /**
     * Mechanical and metrological specifications for pipe / SVP prover.
     */
    public function proverSpecification(): HasOne
    {
        return $this->hasOne(ProverSpecification::class, 'instrument_id');
    }

    /**
     * Calibration verifications for this prover.
     */
    public function proverVerifications(): HasMany
    {
        return $this->hasMany(ProverVerification::class, 'prover_id');
    }

    /**
     * Latest calibration verification for this prover.
     */
    public function latestProverVerification(): HasOne
    {
        return $this->hasOne(ProverVerification::class, 'prover_id')->latestOfMany('calibration_date');
    }

    /**
     * Verifications for this gas chromatograph.
     */
    public function chromatographVerifications(): HasMany
    {
        return $this->hasMany(ChromatographVerification::class, 'instrument_id');
    }

    /**
     * Latest verification for this gas chromatograph.
     */
    public function latestChromatographVerification(): HasOne
    {
        return $this->hasOne(ChromatographVerification::class, 'instrument_id')->latestOfMany('verification_date');
    }

    /**
     * Calibration verifications for this pressure/temperature/level transmitter.
     */
    public function transmitterVerifications(): HasMany
    {
        return $this->hasMany(TransmitterVerification::class, 'instrument_id');
    }

    /**
     * Flow computer verification loops where this transmitter is used as simulated input.
     */
    public function simulatedFlowComputerVerifications(): HasMany
    {
        return $this->hasMany(FlowComputerVerification::class, 'simulated_transmitter_id');
    }

    /**
     * Calibration verifications for this temperature / RTD probe.
     */
    public function probeVerifications(): HasMany
    {
        return $this->hasMany(ProbeVerification::class, 'instrument_id');
    }

    /**
     * Fiscal verification loops for this flow computer.
     */
    public function flowComputerVerifications(): HasMany
    {
        return $this->hasMany(FlowComputerVerification::class, 'instrument_id');
    }

    /**
     * Prover calibration verifications where this standard gauge was used as volume standard.
     */
    public function gaugeProverVerifications(): HasMany
    {
        return $this->hasMany(ProverVerification::class, 'jauge_id');
    }

    /**
     * Check if the instrument is referenced in reports or calibration verification operations.
     */
    public function isLinkedToReportsOrVerifications(): bool
    {
        // 1. Check report_instruments table
        if (DB::getSchemaBuilder()->hasTable('report_instruments')) {
            if (DB::table('report_instruments')->where('instrument_id', $this->id)->exists()) {
                return true;
            }
        }

        // 2. Check category-specific verification tables
        return match ($this->instrument_type) {
            InstrumentType::Transmitter => $this->transmitterVerifications()->exists()
                || $this->simulatedFlowComputerVerifications()->exists(),
            InstrumentType::FlowComputer => $this->flowComputerVerifications()->exists(),
            InstrumentType::Probe => $this->probeVerifications()->exists(),
            InstrumentType::Chromatograph => $this->chromatographVerifications()->exists(),
            InstrumentType::StandardGauge => $this->gaugeProverVerifications()->exists(),
            InstrumentType::Prover => $this->proverVerifications()->exists(),
            default => false,
        };
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Custom dynamic filter for instrument_type supporting single values, CSV, or grouped presets.
     *
     * @param  Builder<self>  $query
     */
    public function filterInstrumentType(Builder $query, mixed $value): void
    {
        if ($value === 'gauges_and_provers') {
            $value = [InstrumentType::StandardGauge->value, InstrumentType::Prover->value];
        } elseif (is_string($value) && str_contains($value, ',')) {
            $value = array_filter(array_map('trim', explode(',', $value)));
        }

        if (is_array($value)) {
            $query->whereIn('instrument_type', $value);
        } else {
            $query->where('instrument_type', $value);
        }
    }

    /**
     * Scope to active instruments.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', InstrumentStatus::Active);
    }
}
