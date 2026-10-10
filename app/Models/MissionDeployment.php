<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MissionDeploymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'mission_id',
    'equipment_id',
    'status',
    'deployed_at',
    'returned_at',
    'notes',
])]
class MissionDeployment extends Model
{
    use HasActivity, HasFactory, SoftDeletes;

    protected $table = 'mission_deployments';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mission_id' => 'integer',
            'equipment_id' => 'integer',
            'status' => MissionDeploymentStatus::class,
            'deployed_at' => 'datetime',
            'returned_at' => 'datetime',
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
                'equipment_id',
                'status',
                'deployed_at',
                'returned_at',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('mission_deployments')
            ->setDescriptionForEvent(fn (string $eventName): string => "Equipment Deployment {$eventName}");
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Associated mission.
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
    }

    /**
     * Mobilized equipment item or vehicle.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }
}
