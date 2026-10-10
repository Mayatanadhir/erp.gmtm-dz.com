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

class ProbeVerification extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'probe_verifications';

    protected $fillable = [
        'instrument_id',
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
            'overall_status' => 'boolean',
        ];
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class, 'instrument_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_mission_id');
    }

    public function points(): HasMany
    {
        return $this->hasMany(ProbeVerificationPoint::class, 'verification_id');
    }

    public function calibrators(): BelongsToMany
    {
        return $this->belongsToMany(
            Equipment::class,
            'probe_verification_calibrators',
            'verification_id',
            'calibrator_id'
        )->withPivot('role');
    }

    /** Calibrateur 1 (Référence Thermique / Four / Bain) */
    public function calibrator1(): BelongsToMany
    {
        return $this->belongsToMany(
            Equipment::class,
            'probe_verification_calibrators',
            'verification_id',
            'calibrator_id'
        )->withPivot('role')->wherePivot('role', 1);
    }

    /** Calibrateur 2 (Mesure Résistance / Multimètre / Décades) */
    public function calibrator2(): BelongsToMany
    {
        return $this->belongsToMany(
            Equipment::class,
            'probe_verification_calibrators',
            'verification_id',
            'calibrator_id'
        )->withPivot('role')->wherePivot('role', 2);
    }

    public function syncCalibratorByRole(?int $calibratorId, int $role): void
    {
        DB::table('probe_verification_calibrators')
            ->where('verification_id', $this->id)
            ->where('role', $role)
            ->delete();

        if ($calibratorId && $calibratorId > 0) {
            DB::table('probe_verification_calibrators')->insert([
                'verification_id' => $this->id,
                'calibrator_id' => $calibratorId,
                'role' => $role,
            ]);
        }
    }
}
