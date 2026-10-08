<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FilterableTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'year',
    'expected_work_days',
    'annual_income',
])]
class IncomeForecast extends Model
{
    use FilterableTrait, HasActivity, HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'income_forecasts';

    /**
     * The attributes that are allowed for dynamic query filtering.
     *
     * @var list<string>
     */
    protected array $filterable = [
        'id',
        'year',
        'expected_work_days',
        'annual_income',
        'created_at',
    ];

    /**
     * Cast attributes to native types.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'expected_work_days' => 'integer',
            'annual_income' => 'decimal:2',
        ];
    }

    /**
     * Get planned daily rate (annual_income / expected_work_days).
     */
    public function getPlannedDailyRateAttribute(): float
    {
        if ($this->expected_work_days > 0) {
            return (float) ($this->annual_income / $this->expected_work_days);
        }

        return 0.0;
    }

    /**
     * Get the activity log options for the IncomeForecast model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'year',
                'expected_work_days',
                'annual_income',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "Annual income forecast has been {$eventName}");
    }
}
