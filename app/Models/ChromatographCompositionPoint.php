<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChromatographCompositionPoint extends Model
{
    use HasFactory;

    protected $table = 'chromatograph_composition_points';

    protected $fillable = [
        'verification_id',
        'step_order',
        'component_name',
        'component_symbol',
        'reference_value',
        'run_1',
        'run_2',
        'run_3',
        'run_4',
        'run_5',
        'mean_value',
        'repeatability',
        'repeatability_limit_astm',
        'repeatability_is_conforme',
        'relative_error_percent',
        'emt_limit_percent',
        'error_is_conforme',
        'is_conforme',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'reference_value' => 'float',
            'run_1' => 'float',
            'run_2' => 'float',
            'run_3' => 'float',
            'run_4' => 'float',
            'run_5' => 'float',
            'mean_value' => 'float',
            'repeatability' => 'float',
            'repeatability_limit_astm' => 'float',
            'repeatability_is_conforme' => 'boolean',
            'relative_error_percent' => 'float',
            'emt_limit_percent' => 'float',
            'error_is_conforme' => 'boolean',
            'is_conforme' => 'boolean',
        ];
    }

    public function verification(): BelongsTo
    {
        return $this->belongsTo(ChromatographVerification::class, 'verification_id');
    }
}
