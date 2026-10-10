<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EquipmentCategory;
use App\Enums\MissionStatus;
use App\Traits\FilterableTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'site_id',
    'contract_id',
    'reference',
    'start_date',
    'end_date',
    'mob_dmob_days',
    'description',
    'status',
])]
class Mission extends Model
{
    use FilterableTrait, HasActivity, HasFactory, SoftDeletes;

    protected $table = 'missions';

    /**
     * Attributes allowed for dynamic query filtering via FilterableTrait.
     *
     * @var list<string>
     */
    protected array $filterable = [
        'id',
        'site_id',
        'contract_id',
        'reference',
        'status',
        'start_date',
        'end_date',
        'created_at',
    ];

    /**
     * Attributes searched via global search keyword.
     *
     * @var list<string>
     */
    protected array $searchable = [
        'reference',
        'description',
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
            'contract_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'mob_dmob_days' => 'integer',
            'status' => MissionStatus::class,
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
                'site_id',
                'contract_id',
                'start_date',
                'end_date',
                'mob_dmob_days',
                'status',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('missions')
            ->setDescriptionForEvent(fn (string $eventName): string => "Mission {$eventName}");
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Industrial site where calibration is performed.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    /**
     * Commercial contract linked to this mission.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    /**
     * Periodic billing attachments linked to this mission.
     *
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'mission_id');
    }

    /**
     * All employee mission orders assigned to this mission.
     */
    public function missionOrders(): HasMany
    {
        return $this->hasMany(MissionOrder::class, 'mission_id');
    }

    /**
     * All metrology reports linked to this mission.
     *
     * @return HasMany<Report, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'mission_id');
    }

    /**
     * Active team leader assignment.
     */
    public function teamLeaderOrder(): HasOne
    {
        return $this->hasOne(MissionOrder::class, 'mission_id')->where('is_leader', true);
    }

    /**
     * Employees deployed on this mission via mission_orders pivot.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'mission_orders', 'mission_id', 'employee_id')
            ->wherePivotNull('deleted_at')
            ->withPivot([
                'id',
                'is_leader',
                'status',
                'started_at',
                'ended_at',
                'daily_rate',
                'order_reference',
                'destination',
                'vehicle_id',
                'all_vehicles',
            ])
            ->withTimestamps();
    }

    /**
     * Equipment deployments for tools, calibrators, and vehicles.
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(MissionDeployment::class, 'mission_id');
    }

    /**
     * Equipments mobilized on this mission via mission_deployments pivot.
     */
    public function equipments(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'mission_deployments', 'mission_id', 'equipment_id')
            ->wherePivotNull('deleted_at')
            ->withPivot([
                'id',
                'status',
                'deployed_at',
                'returned_at',
                'notes',
            ])
            ->withTimestamps();
    }

    /**
     * Expenses / Charges assigned to this mission.
     *
     * @return HasMany<Expense, $this>
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Expense::class, 'mission_id');
    }

    // ==========================================
    // Accessors & Helpers
    // ==========================================

    /**
     * Calculate total mission calendar duration in days.
     */
    public function getTotalDaysAttribute(): int
    {
        if (! $this->start_date || ! $this->end_date) {
            return 0;
        }

        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Calculate net operational days excluding mobilization/demobilization.
     */
    public function getOperationalDaysAttribute(): int
    {
        return max(0, $this->total_days - (int) $this->mob_dmob_days);
    }

    /**
     * Get assigned mission team leader employee.
     */
    public function getTeamLeaderAttribute(): ?Employee
    {
        return $this->teamLeaderOrder?->employee;
    }

    /**
     * Get technical equipment deployments excluding transport vehicles.
     *
     * @return Collection<int, MissionDeployment>
     */
    public function getTechnicalDeploymentsAttribute()
    {
        return $this->deployments->reject(function (MissionDeployment $deployment) {
            $category = $deployment->equipment?->category;

            return $category === EquipmentCategory::Vehicle
                || ($category instanceof \BackedEnum ? $category->value === 'vehicle' : $category === 'vehicle');
        });
    }
}
