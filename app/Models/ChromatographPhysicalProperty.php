<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChromatographPhysicalProperty extends Model
{
    use HasFactory;

    protected $table = 'chromatograph_physical_properties';

    protected $fillable = [
        'verification_id',
        'property_name',
        'property_symbol',
        'unit',
        'reference_value',
        'run_1',
        'run_2',
        'run_3',
        'run_4',
        'run_5',
        'mean_value',
        'relative_error_percent',
        'emt_limit_percent',
        'repeatability',
        'repeatability_limit',
        'is_conforme',
    ];

    protected function casts(): array
    {
        return [
            'reference_value' => 'float',
            'run_1' => 'float',
            'run_2' => 'float',
            'run_3' => 'float',
            'run_4' => 'float',
            'run_5' => 'float',
            'mean_value' => 'float',
            'relative_error_percent' => 'float',
            'emt_limit_percent' => 'float',
            'repeatability' => 'float',
            'repeatability_limit' => 'float',
            'is_conforme' => 'boolean',
        ];
    }

    public function verification(): BelongsTo
    {
        return $this->belongsTo(ChromatographVerification::class, 'verification_id');
    }
}
