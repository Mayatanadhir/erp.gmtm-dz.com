<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class FlowComputerVerification extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'flow_computer_verifications';

    protected $fillable = [
        'instrument_id',
        'simulated_transmitter_id',
        'shunt_resistance',
        'report_mission_id',
        'verification_date',
        'ambient_temperature',
        'ambient_pressure',
        'overall_status',
    ];

    protected function casts(): array
    {
        return [
            'verification_date' => 'date',
            'ambient_temperature' => 'float',
            'ambient_pressure' => 'float',
            'shunt_resistance' => 'float',
            'overall_status' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class, 'instrument_id');
    }

    public function simulatedTransmitter(): BelongsTo
    {
        return $this->belongsTo(Instrument::class, 'simulated_transmitter_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_mission_id');
    }

    public function points(): HasMany
    {
        return $this->hasMany(FlowComputerVerificationPoint::class, 'verification_id');
    }

    public function calibrators(): BelongsToMany
    {
        return $this->belongsToMany(
            Equipment::class,
            'flow_computer_verification_calibrators',
            'verification_id',
            'calibrator_id'
        )->withPivot('role');
    }

    /** Calibrateur 1 (mA / V Générateur / Injecteur) */
    public function calibrator1(): BelongsToMany
    {
        return $this->belongsToMany(
            Equipment::class,
            'flow_computer_verification_calibrators',
            'verification_id',
            'calibrator_id'
        )->withPivot('role')->wherePivot('role', 1);
    }

    /** Calibrateur 2 (Grandeur physique / Pression / Température) */
    public function calibrator2(): BelongsToMany
    {
        return $this->belongsToMany(
            Equipment::class,
            'flow_computer_verification_calibrators',
            'verification_id',
            'calibrator_id'
        )->withPivot('role')->wherePivot('role', 2);
    }

    public function syncCalibratorByRole(?int $calibratorId, int $role): void
    {
        DB::table('flow_computer_verification_calibrators')
            ->where('verification_id', $this->id)
            ->where('role', $role)
            ->delete();

        if ($calibratorId && $calibratorId > 0) {
            DB::table('flow_computer_verification_calibrators')->insert([
                'verification_id' => $this->id,
                'calibrator_id' => $calibratorId,
                'role' => $role,
            ]);
        }
    }
}
