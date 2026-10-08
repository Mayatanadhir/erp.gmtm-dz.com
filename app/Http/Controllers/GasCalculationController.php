<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CalculateGasRequest;
use App\Services\Aga8Service;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Throwable;

class GasCalculationController extends Controller
{
    /**
     * API endpoint to calculate natural gas properties according to AGA8 Detail & ISO 6976.
     */
    public function calculate(CalculateGasRequest $request, Aga8Service $aga8): JsonResponse
    {
        $validated = $request->validated();

        $pressure = (float) $validated['pressure'];
        $pressureUnit = (string) ($validated['pressure_unit'] ?? 'kpa');

        $temperature = (float) $validated['temperature'];
        $tempUnit = (string) ($validated['temperature_unit'] ?? 'k');

        /** @var array<string, float>|array<int, float> $composition */
        $composition = $validated['composition'];

        try {
            $results = $aga8->calculateWithUnits(
                pressure: $pressure,
                pressureUnit: $pressureUnit,
                temperature: $temperature,
                tempUnit: $tempUnit,
                composition: $composition
            );

            return response()->json([
                'success' => true,
                'inputs' => [
                    'pressure' => $pressure,
                    'pressure_unit' => $pressureUnit,
                    'temperature' => $temperature,
                    'temperature_unit' => $tempUnit,
                    'composition' => $composition,
                ],
                'energy_properties_iso6976' => $results['energy_properties_iso6976'] ?? null,
                'thermodynamic_properties_aga8' => $results['thermodynamic_properties_aga8'] ?? null,
                'data' => $results,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('Calculation failed: :error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * List of all 21 approved components with names and formulas.
     */
    public function components(Aga8Service $aga8): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'components' => $aga8->getComponents(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
