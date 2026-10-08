<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GrandeurDiscipline;
use App\Models\CalibrationCertificate;
use App\Models\CalibrationInterpolation;
use App\Models\EquipmentSpecification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InterpolationService
{
    /**
     * Standard curated distinct color palette for the 5 comparative points.
     *
     * @var list<string>
     */
    protected array $pointColors = [
        '#0284c7', // Sky Blue (Point 1 - Min)
        '#059669', // Emerald Green (Point 2 - 25%)
        '#f59e0b', // Amber (Point 3 - 50% Mid)
        '#8b5cf6', // Violet (Point 4 - 75%)
        '#e11d48', // Rose Red (Point 5 - Max)
    ];

    /**
     * Perform linear interpolation and calculate uncertainty using the Florian Platel model.
     *
     * @param  array<int, array{nominal: float, value: float, uncertainty: float}>  $points  Sorted ascending by nominal
     * @param  float  $targetX  Target point nominal value
     * @param  bool  $correlated  True for correlated certificate points (r = 1, default)
     * @return array{
     *     target_x: float,
     *     interpolated_value: float,
     *     theta: float,
     *     step_p: float,
     *     curvature_a2: float,
     *     max_modeling_error: float,
     *     u_exp: float,
     *     u_mod: float,
     *     u_combined: float,
     *     expanded_uncertainty: float,
     *     is_exact_point: bool,
     *     confidence_interval: array{lower: float, upper: float}
     * }
     */
    public function interpolate(array $points, float $targetX, bool $correlated = true): array
    {
        if (count($points) === 0) {
            throw new InvalidArgumentException('No calibration points provided for interpolation.');
        }

        // 1. Check if targetX matches an exact calibration point (within epsilon)
        foreach ($points as $pt) {
            if (abs($pt['nominal'] - $targetX) < 1e-7) {
                $val = (float) $pt['value'];
                $uExp = (float) $pt['uncertainty'];
                $expandedU = round(2.0 * $uExp, 4);

                return [
                    'target_x' => $targetX,
                    'interpolated_value' => round($val, 5),
                    'theta' => 0.0,
                    'step_p' => 0.0,
                    'curvature_a2' => 0.0,
                    'max_modeling_error' => 0.0,
                    'u_exp' => round($uExp, 5),
                    'u_mod' => 0.0,
                    'u_combined' => round($uExp, 5),
                    'expanded_uncertainty' => $expandedU,
                    'is_exact_point' => true,
                    'confidence_interval' => [
                        'lower' => round($val - $expandedU, 5),
                        'upper' => round($val + $expandedU, 5),
                    ],
                ];
            }
        }

        // 2. Extrapolation check
        $minNominal = $points[0]['nominal'];
        $maxNominal = $points[count($points) - 1]['nominal'];

        if ($targetX < $minNominal || $targetX > $maxNominal) {
            throw new InvalidArgumentException(
                sprintf('Target value %s is outside calibrated span [%s, %s]. Extrapolation is strictly forbidden.', $targetX, $minNominal, $maxNominal)
            );
        }

        // 3. Find the interval [x1, x2] enclosing targetX
        $x1Index = 0;
        $count = count($points);
        for ($i = 0; $i < $count - 1; $i++) {
            if ($targetX >= $points[$i]['nominal'] && $targetX <= $points[$i + 1]['nominal']) {
                $x1Index = $i;
                break;
            }
        }

        $x1 = $points[$x1Index]['nominal'];
        $y1 = $points[$x1Index]['value'];
        $u1 = $points[$x1Index]['uncertainty'];

        $x2 = $points[$x1Index + 1]['nominal'];
        $y2 = $points[$x1Index + 1]['value'];
        $u2 = $points[$x1Index + 1]['uncertainty'];

        $stepP = $x2 - $x1;
        if ($stepP <= 0.0) {
            throw new InvalidArgumentException('Consecutive points must have distinct nominal values.');
        }

        // 4. Positional parameter theta
        $theta = ($targetX - $x1) / $stepP;

        // 5. Interpolated value
        $interpolatedVal = (1.0 - $theta) * $y1 + $theta * $y2;

        // 6. Propagated experimental uncertainty u_exp
        if ($correlated) {
            $uExp = (1.0 - $theta) * $u1 + $theta * $u2;
        } else {
            $uExp = sqrt(pow((1.0 - $theta) * $u1, 2) + pow($theta * $u2, 2));
        }

        // 7. Curvature coefficient a2 using third point x3
        $curvatureA2 = 0.0;
        if ($count >= 3) {
            if ($x1Index + 2 < $count) {
                // Next point exists
                $x3 = $points[$x1Index + 2]['nominal'];
                $y3 = $points[$x1Index + 2]['value'];
            } elseif ($x1Index - 1 >= 0) {
                // Previous point exists
                $x3 = $points[$x1Index - 1]['nominal'];
                $y3 = $points[$x1Index - 1]['value'];
            } else {
                $x3 = null;
                $y3 = null;
            }

            if ($x3 !== null && $y3 !== null) {
                // Second divided difference for curvature a_2
                $diff1 = ($y2 - $y1) / ($x2 - $x1);
                $diff2 = ($y3 - $y2) / ($x3 - $x2);
                $denom = $x3 - $x1;
                if (abs($denom) > 1e-9) {
                    $curvatureA2 = ($diff2 - $diff1) / $denom;
                }
            }
        }

        // 8. Modeling uncertainty u_mod (Florian Platel rectangular model)
        $maxModelingError = (pow($stepP, 2) / 4.0) * abs($curvatureA2);
        $uMod = $maxModelingError / sqrt(3.0);

        // 9. Combined & Expanded Uncertainty
        $uCombined = sqrt(pow($uExp, 2) + pow($uMod, 2));
        $expandedU = round(2.0 * $uCombined, 4);

        return [
            'target_x' => $targetX,
            'interpolated_value' => round($interpolatedVal, 5),
            'theta' => round($theta, 4),
            'step_p' => round($stepP, 4),
            'curvature_a2' => round($curvatureA2, 6),
            'max_modeling_error' => round($maxModelingError, 5),
            'u_exp' => round($uExp, 5),
            'u_mod' => round($uMod, 5),
            'u_combined' => round($uCombined, 5),
            'expanded_uncertainty' => $expandedU,
            'is_exact_point' => false,
            'confidence_interval' => [
                'lower' => round($interpolatedVal - $expandedU, 5),
                'upper' => round($interpolatedVal + $expandedU, 5),
            ],
        ];
    }

    /**
     * Get all equipment specifications configured for the equipment of the certificate.
     * Equipment is the authoritative source for standards and specifications.
     *
     * @return Collection<int, EquipmentSpecification>
     */
    public function getCertificateSpecifications(CalibrationCertificate $certificate): Collection
    {
        if ($certificate->equipment && $certificate->equipment->specifications()->exists()) {
            return $certificate->equipment
                ->specifications()
                ->with(['grandeur'])
                ->get();
        }

        // Fallback: discover from certificate points if equipment has no specifications configured
        $specIds = $certificate->calibrationPoints
            ->pluck('equipment_specification_id')
            ->filter()
            ->unique()
            ->values();

        if ($specIds->isEmpty()) {
            return collect();
        }

        return EquipmentSpecification::whereIn('id', $specIds)
            ->with(['grandeur'])
            ->get();
    }

    /**
     * Filter certificate points, strictly ignoring any points outside [min, max] per directive,
     * and optionally filtering by equipment_specification_id.
     *
     * @param  iterable<mixed>  $calibrationPoints
     * @return array<int, array{nominal: float, value: float, uncertainty: float, equipment_specification_id: ?int}>
     */
    public function getCleanPoints(
        iterable $calibrationPoints,
        ?float $minBound = null,
        ?float $maxBound = null,
        ?int $equipmentSpecificationId = null
    ): array {
        $raw = [];
        foreach ($calibrationPoints as $pt) {
            $specId = is_array($pt)
                ? ($pt['equipment_specification_id'] ?? null)
                : $pt->equipment_specification_id;

            $nominal = is_array($pt) ? (float) ($pt['nominal'] ?? $pt['nominal_value'] ?? 0) : (float) $pt->nominal_value;

            // If a specific specification ID is requested, strictly filter by it
            if ($equipmentSpecificationId !== null) {
                if ($specId !== null) {
                    if ((int) $specId !== (int) $equipmentSpecificationId) {
                        continue;
                    }
                } else {
                    // Fallback for unlinked points: match by specification range
                    $targetSpec = EquipmentSpecification::find($equipmentSpecificationId);
                    if ($targetSpec) {
                        $min = (float) $targetSpec->range_min;
                        $max = (float) $targetSpec->range_max;
                        $span = abs($max - $min);
                        $tol = max(1.0, $span * 0.15);
                        if ($nominal < ($min - $tol) || $nominal > ($max + $tol)) {
                            continue;
                        }
                    }
                }
            }

            $val = is_array($pt) ? (float) ($pt['value'] ?? $pt['correction'] ?? 0) : (float) $pt->correction;
            $unc = is_array($pt) ? (float) ($pt['uncertainty'] ?? 0) : (float) $pt->uncertainty;

            // Directive: Ignore any point outside the specified min/max bounds
            if ($minBound !== null && $nominal < $minBound) {
                continue;
            }
            if ($maxBound !== null && $nominal > $maxBound) {
                continue;
            }

            $raw[] = [
                'nominal' => $nominal,
                'value' => $val,
                'uncertainty' => $unc,
                'equipment_specification_id' => $specId ? (int) $specId : null,
            ];
        }

        // Sort ascending by nominal
        usort($raw, fn ($a, $b) => $a['nominal'] <=> $b['nominal']);

        // Remove duplicate nominal values keeping the first
        $cleaned = [];
        $seen = [];
        foreach ($raw as $item) {
            $key = (string) $item['nominal'];
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $cleaned[] = $item;
            }
        }

        return $cleaned;
    }

    /**
     * Generate the standard 5-point grid for a calibration certificate and specification.
     *
     * @param  array<int, float>|null  $customPoints  Optional custom 5 points [X1 < X2 < X3 < X4 < X5]
     * @return array{
     *     has_data: bool,
     *     span_min: float,
     *     span_max: float,
     *     parameter_name: string,
     *     unit_symbol: string,
     *     equipment_specification_id: ?int,
     *     points: list<array<string, mixed>>,
     *     chart_data: array{labels: list<string>, values: list<float>, u_upper: list<float>, u_lower: list<float>, unit: string, parameter_name: string}
     * }
     */
    public function generateFivePointGrid(
        CalibrationCertificate $certificate,
        ?array $customPoints = null,
        ?float $minBound = null,
        ?float $maxBound = null,
        ?int $equipmentSpecificationId = null
    ): array {
        $certificatePoints = $certificate->calibrationPoints;

        // Resolve specification from parameter ID or fallback to first specification from equipment
        $spec = null;
        if ($equipmentSpecificationId !== null) {
            $spec = EquipmentSpecification::with('grandeur')->find($equipmentSpecificationId);
        } elseif ($certificate->equipment && $certificate->equipment->specifications()->exists()) {
            $spec = $certificate->equipment->specifications()->with('grandeur')->first();
            $equipmentSpecificationId = $spec?->id;
        } elseif ($certificatePoints->first()?->equipment_specification_id) {
            $spec = EquipmentSpecification::with('grandeur')->find($certificatePoints->first()->equipment_specification_id);
            $equipmentSpecificationId = $spec?->id;
        }

        $parameterName = $spec?->grandeur?->name ?? __('Standard Parameter');
        $unitSymbol = $spec?->grandeur?->symbol ?? '';
        $grandeurType = $spec?->grandeur?->type?->value ?? 'measurement';
        $grandeurTypeLabel = $spec?->grandeur?->type?->label() ?? __('Measurement (Sensor / In)');
        $grandeurTypeBadge = $spec?->grandeur?->type?->badgeVariant() ?? 'success';

        $discipline = $spec?->discipline() ?? GrandeurDiscipline::Generic;
        $disciplineKey = $discipline->value;
        $disciplineLabel = $discipline->label();
        $disciplineIcon = $discipline->icon();
        $disciplineColor = $discipline->chartHexColor();
        $disciplineBadge = $discipline->badgeClass();
        $disciplineTextClass = $discipline->textClass();
        $disciplineIconContainer = $discipline->iconContainerClass();

        if ($certificatePoints->count() < 2) {
            return [
                'has_data' => false,
                'span_min' => $spec?->range_min ?? 0.0,
                'span_max' => $spec?->range_max ?? 0.0,
                'parameter_name' => $parameterName,
                'unit_symbol' => $unitSymbol,
                'grandeur_type' => $grandeurType,
                'grandeur_type_label' => $grandeurTypeLabel,
                'grandeur_type_badge' => $grandeurTypeBadge,
                'discipline' => $disciplineKey,
                'discipline_label' => $disciplineLabel,
                'discipline_icon' => $disciplineIcon,
                'discipline_color' => $disciplineColor,
                'discipline_badge' => $disciplineBadge,
                'discipline_text_class' => $disciplineTextClass,
                'discipline_icon_container' => $disciplineIconContainer,
                'equipment_specification_id' => $equipmentSpecificationId,
                'points' => [],
                'chart_data' => [
                    'labels' => [],
                    'values' => [],
                    'u_upper' => [],
                    'u_lower' => [],
                    'unit' => $unitSymbol,
                    'parameter_name' => $parameterName,
                    'grandeur_type' => $grandeurType,
                    'discipline' => $disciplineKey,
                    'discipline_color' => $disciplineColor,
                    'discipline_icon' => $disciplineIcon,
                ],
            ];
        }

        // 1. Determine natural min and max from certificate points for this specification
        $allPoints = $this->getCleanPoints($certificatePoints, null, null, $equipmentSpecificationId);
        if (count($allPoints) < 2) {
            return [
                'has_data' => false,
                'span_min' => $spec?->range_min ?? 0.0,
                'span_max' => $spec?->range_max ?? 0.0,
                'parameter_name' => $parameterName,
                'unit_symbol' => $unitSymbol,
                'grandeur_type' => $grandeurType,
                'grandeur_type_label' => $grandeurTypeLabel,
                'grandeur_type_badge' => $grandeurTypeBadge,
                'discipline' => $disciplineKey,
                'discipline_label' => $disciplineLabel,
                'discipline_icon' => $disciplineIcon,
                'discipline_color' => $disciplineColor,
                'discipline_badge' => $disciplineBadge,
                'discipline_text_class' => $disciplineTextClass,
                'discipline_icon_container' => $disciplineIconContainer,
                'equipment_specification_id' => $equipmentSpecificationId,
                'points' => [],
                'chart_data' => [
                    'labels' => [],
                    'values' => [],
                    'u_upper' => [],
                    'u_lower' => [],
                    'unit' => $unitSymbol,
                    'parameter_name' => $parameterName,
                    'grandeur_type' => $grandeurType,
                    'discipline' => $disciplineKey,
                    'discipline_color' => $disciplineColor,
                    'discipline_icon' => $disciplineIcon,
                ],
            ];
        }

        $naturalMin = $allPoints[0]['nominal'];
        $naturalMax = $allPoints[count($allPoints) - 1]['nominal'];

        $effectiveMin = $minBound !== null ? max($minBound, $naturalMin) : $naturalMin;
        $effectiveMax = $maxBound !== null ? min($maxBound, $naturalMax) : $naturalMax;

        if ($effectiveMin >= $effectiveMax) {
            $effectiveMin = $naturalMin;
            $effectiveMax = $naturalMax;
        }

        // 2. Filter points strictly within bounds (ignoring outside points per directive)
        $cleanPoints = $this->getCleanPoints($certificatePoints, $effectiveMin, $effectiveMax, $equipmentSpecificationId);
        if (count($cleanPoints) < 2) {
            $cleanPoints = $allPoints;
            $effectiveMin = $naturalMin;
            $effectiveMax = $naturalMax;
        }

        // 3. Determine the 5 target points
        $fiveX = [];
        if ($customPoints !== null && count($customPoints) === 5) {
            sort($customPoints);
            $isValid = true;
            foreach ($customPoints as $val) {
                if ($val < $effectiveMin || $val > $effectiveMax) {
                    $isValid = false;
                    break;
                }
            }
            if ($isValid) {
                $fiveX = array_values($customPoints);
            }
        }

        if (count($fiveX) !== 5) {
            // Automatic uniform 5-step division: 0%, 25%, 50%, 75%, 100%
            $step = ($effectiveMax - $effectiveMin) / 4.0;
            $fiveX = [
                $effectiveMin,
                round($effectiveMin + 0.25 * ($effectiveMax - $effectiveMin), 4),
                round($effectiveMin + 0.50 * ($effectiveMax - $effectiveMin), 4),
                round($effectiveMin + 0.75 * ($effectiveMax - $effectiveMin), 4),
                $effectiveMax,
            ];
        }

        // 4. Calculate interpolation results for each of the 5 points
        $results = [];
        $chartLabels = [];
        $chartValues = [];
        $chartUpper = [];
        $chartLower = [];

        foreach ($fiveX as $idx => $targetX) {
            $interp = $this->interpolate($cleanPoints, (float) $targetX, true);
            $interp['point_index'] = $idx + 1;
            $interp['unit'] = $unitSymbol;
            $results[] = $interp;

            $chartLabels[] = (string) $interp['target_x'];
            $chartValues[] = $interp['interpolated_value'];
            $chartUpper[] = round($interp['interpolated_value'] + $interp['expanded_uncertainty'], 5);
            $chartLower[] = round($interp['interpolated_value'] - $interp['expanded_uncertainty'], 5);
        }

        return [
            'has_data' => true,
            'span_min' => $effectiveMin,
            'span_max' => $effectiveMax,
            'parameter_name' => $parameterName,
            'unit_symbol' => $unitSymbol,
            'grandeur_type' => $grandeurType,
            'grandeur_type_label' => $grandeurTypeLabel,
            'grandeur_type_badge' => $grandeurTypeBadge,
            'discipline' => $disciplineKey,
            'discipline_label' => $disciplineLabel,
            'discipline_icon' => $disciplineIcon,
            'discipline_color' => $disciplineColor,
            'discipline_badge' => $disciplineBadge,
            'discipline_text_class' => $disciplineTextClass,
            'discipline_icon_container' => $disciplineIconContainer,
            'equipment_specification_id' => $equipmentSpecificationId,
            'points' => $results,
            'chart_data' => [
                'labels' => $chartLabels,
                'values' => $chartValues,
                'u_upper' => $chartUpper,
                'u_lower' => $chartLower,
                'unit' => $unitSymbol,
                'parameter_name' => $parameterName,
                'grandeur_type' => $grandeurType,
                'discipline' => $disciplineKey,
                'discipline_color' => $disciplineColor,
                'discipline_icon' => $disciplineIcon,
            ],
        ];
    }

    /**
     * Save/persist the 5-point grid into the calibration_interpolations table.
     *
     * @param  array<int, float>  $fivePoints
     * @return list<CalibrationInterpolation>
     */
    public function saveFivePointGrid(
        CalibrationCertificate $certificate,
        array $fivePoints,
        ?float $minBound = null,
        ?float $maxBound = null,
        ?int $equipmentSpecificationId = null
    ): array {
        $grid = $this->generateFivePointGrid($certificate, $fivePoints, $minBound, $maxBound, $equipmentSpecificationId);
        if (! $grid['has_data'] || count($grid['points']) !== 5) {
            throw new InvalidArgumentException('Cannot compute 5-point interpolation grid for this certificate.');
        }

        $specId = $grid['equipment_specification_id'] ?? $equipmentSpecificationId;

        return DB::transaction(function () use ($certificate, $grid, $specId) {
            // Delete existing records for this certificate and specification
            $query = CalibrationInterpolation::where('calibration_certificate_id', $certificate->id);
            if ($specId !== null) {
                $query->where('equipment_specification_id', $specId);
            } else {
                $query->whereNull('equipment_specification_id');
            }
            $query->delete();

            $saved = [];
            foreach ($grid['points'] as $pt) {
                $saved[] = CalibrationInterpolation::create([
                    'calibration_certificate_id' => $certificate->id,
                    'equipment_specification_id' => $specId,
                    'point_index' => $pt['point_index'],
                    'target_nominal' => $pt['target_x'],
                    'interpolated_value' => $pt['interpolated_value'],
                    'experimental_uncertainty' => $pt['u_exp'],
                    'curvature_coefficient' => $pt['curvature_a2'],
                    'modeling_uncertainty' => $pt['u_mod'],
                    'combined_uncertainty' => $pt['u_combined'],
                    'expanded_uncertainty' => $pt['expanded_uncertainty'],
                    'is_exact_point' => (bool) $pt['is_exact_point'],
                ]);
            }

            return $saved;
        });
    }

    /**
     * Generate multi-certificate comparison datasets across historical certificates of the same equipment,
     * strictly isolated by the physical specification and unit.
     *
     * @param  array<int, float>  $fivePoints  The 5 selected reference setpoints
     * @return array{
     *     labels: list<string>,
     *     datasets: list<array<string, mixed>>,
     *     has_data: bool
     * }
     */
    public function generateMultiCertificateComparison(
        CalibrationCertificate $currentCertificate,
        array $fivePoints,
        ?int $equipmentSpecificationId = null,
        string $unitSymbol = ''
    ): array {
        if (! $currentCertificate->equipment_id) {
            return ['labels' => [], 'datasets' => [], 'has_data' => false];
        }

        // Fetch all certificates of this equipment having calibration points and dates
        $certificates = CalibrationCertificate::where('equipment_id', $currentCertificate->equipment_id)
            ->whereNotNull('calibration_date')
            ->with(['calibrationPoints.equipmentSpecification.grandeur'])
            ->orderBy('calibration_date', 'asc')
            ->get();

        if ($certificates->count() === 0) {
            return ['labels' => [], 'datasets' => [], 'has_data' => false];
        }

        $targetGrandeurId = null;
        $targetGrandeurType = null;
        if ($equipmentSpecificationId !== null) {
            $spec = EquipmentSpecification::with('grandeur')->find($equipmentSpecificationId);
            $targetGrandeurId = $spec?->grandeur_id;
            $targetGrandeurType = $spec?->grandeur?->type?->value;
            if (empty($unitSymbol) && $spec?->grandeur?->symbol) {
                $unitSymbol = $spec->grandeur->symbol;
            }
        }

        $dates = [];
        // Structure: pointsData[targetX_index][date] = value
        $pointsEvolution = [0 => [], 1 => [], 2 => [], 3 => [], 4 => []];

        foreach ($certificates as $cert) {
            $dateStr = Carbon::parse($cert->calibration_date)->format('Y-m-d');

            // Filter points strictly belonging to the same specification or grandeur and grandeur type
            $matchedPoints = $cert->calibrationPoints->filter(function ($pt) use ($equipmentSpecificationId, $targetGrandeurId, $targetGrandeurType) {
                if ($equipmentSpecificationId !== null && (int) $pt->equipment_specification_id === (int) $equipmentSpecificationId) {
                    return true;
                }
                if ($targetGrandeurId !== null && (int) $pt->equipmentSpecification?->grandeur_id === (int) $targetGrandeurId) {
                    // Strict separation: never mix Measurement with Source
                    if ($targetGrandeurType !== null) {
                        return $pt->equipmentSpecification?->grandeur?->type?->value === $targetGrandeurType;
                    }

                    return true;
                }

                return $equipmentSpecificationId === null;
            });

            $pts = $this->getCleanPoints($matchedPoints);
            if (count($pts) < 2) {
                continue;
            }

            $dates[] = $dateStr;

            $certMin = $pts[0]['nominal'];
            $certMax = $pts[count($pts) - 1]['nominal'];

            foreach ($fivePoints as $ptIndex => $targetX) {
                // If targetX is within this certificate's bounds, interpolate; otherwise ignore (null)
                if ($targetX >= $certMin && $targetX <= $certMax) {
                    try {
                        $res = $this->interpolate($pts, (float) $targetX, true);
                        $pointsEvolution[$ptIndex][$dateStr] = $res['interpolated_value'];
                    } catch (\Throwable) {
                        $pointsEvolution[$ptIndex][$dateStr] = null;
                    }
                } else {
                    // Ignore points outside certificate range per directive
                    $pointsEvolution[$ptIndex][$dateStr] = null;
                }
            }
        }

        $dates = array_values(array_unique($dates));
        sort($dates);

        // Build 5 datasets (one for each of the 5 points)
        $datasets = [];
        foreach ($fivePoints as $ptIndex => $targetX) {
            $seriesData = [];
            foreach ($dates as $date) {
                $seriesData[] = $pointsEvolution[$ptIndex][$date] ?? null;
            }

            $color = $this->pointColors[$ptIndex % count($this->pointColors)];
            $typeTag = $targetGrandeurType === 'source' ? ' [OUT]' : ($targetGrandeurType === 'measurement' ? ' [IN]' : '');
            $pointLabel = ! empty($unitSymbol)
                ? sprintf(__('Point %d (%s %s)'), $ptIndex + 1, $targetX, $unitSymbol).$typeTag
                : sprintf(__('Point %d (%s)'), $ptIndex + 1, $targetX).$typeTag;

            $datasets[] = [
                'label' => $pointLabel,
                'data' => $seriesData,
                'borderColor' => $color,
                'backgroundColor' => $color.'33',
                'borderWidth' => 2,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
                'spanGaps' => true,
                'tension' => 0.1,
            ];
        }

        return [
            'labels' => $dates,
            'datasets' => $datasets,
            'has_data' => count($dates) > 0,
            'unit' => $unitSymbol,
        ];
    }
}
