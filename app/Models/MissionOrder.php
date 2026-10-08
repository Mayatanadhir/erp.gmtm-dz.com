<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MissionOrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'mission_id',
    'employee_id',
    'is_leader',
    'status',
    'started_at',
    'ended_at',
    'daily_rate',
    'order_reference',
    'destination',
    'vehicle_id',
    'all_vehicles',
])]
class MissionOrder extends Model
{
    use HasActivity, HasFactory, SoftDeletes;

    protected $table = 'mission_orders';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mission_id' => 'integer',
            'employee_id' => 'integer',
            'vehicle_id' => 'integer',
            'is_leader' => 'boolean',
            'status' => MissionOrderStatus::class,
            'started_at' => 'date',
            'ended_at' => 'date',
            'daily_rate' => 'decimal:2',
            'all_vehicles' => 'boolean',
        ];
    }

    /**
     * Activity log configuration.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'mission_id',
                'employee_id',
                'is_leader',
                'status',
                'order_reference',
                'destination',
                'daily_rate',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('mission_orders')
            ->setDescriptionForEvent(fn (string $eventName): string => "Mission Order {$eventName}");
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Governing mission.
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
    }

    /**
     * Assigned employee.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Designated transport vehicle if assigned.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'vehicle_id');
    }

    // ==========================================
    // Accessors & Calculation Helpers
    // ==========================================

    /**
     * Effective duration of employee deployment in days.
     */
    public function getDaysCountAttribute(): int
    {
        if (! $this->started_at || ! $this->ended_at) {
            return 0;
        }

        return (int) $this->started_at->diffInDays($this->ended_at) + 1;
    }

    /**
     * Total compensation for this travel order (days * daily_rate).
     */
    public function getTotalAmountAttribute(): float
    {
        return (float) ($this->days_count * (float) $this->daily_rate);
    }
}
