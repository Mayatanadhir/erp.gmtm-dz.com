<?php

declare(strict_types=1);

namespace App\Http\Requests\Calibration;

use App\Constants\MetrologyConstants;
use App\DTOs\Metrology\ProverVerificationDTO;
use Illuminate\Foundation\Http\FormRequest;

final class StoreProverVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mergeData = [];

        if (! $this->filled('reference_temperature')) {
            $mergeData['reference_temperature'] = MetrologyConstants::REF_TEMPERATURE_C;
        }

        if (! $this->filled('pressure_unit')) {
            $mergeData['pressure_unit'] = 'bar';
        }

        if (! $this->filled('calibration_date')) {
            $mergeData['calibration_date'] = now()->toDateString();
        }

        if (! empty($mergeData)) {
            $this->merge($mergeData);
        }

        // Sanitize and flatten runs array if nested fills are provided
        if ($this->has('runs') && is_array($this->input('runs'))) {
            $sanitizedRuns = [];
            $flatIdx = 0;
            foreach ($this->input('runs') as $item) {
                if (is_array($item) && isset($item['fills']) && is_array($item['fills'])) {
                    $runNumber = (int) ($item['run_number'] ?? 1);
                    foreach ($item['fills'] as $fill) {
                        if (is_array($fill)) {
                            $fill['run_number'] = $runNumber;
                            $fill['fill_number'] = (int) ($fill['fill_number'] ?? 1);
                            $fill['prover_pressure'] = isset($fill['prover_pressure']) && $fill['prover_pressure'] !== '' ? $fill['prover_pressure'] : '0.0000';
                            $sanitizedRuns[$flatIdx++] = $fill;
                        }
                    }
                } elseif (is_array($item)) {
                    $item['prover_pressure'] = isset($item['prover_pressure']) && $item['prover_pressure'] !== '' ? $item['prover_pressure'] : '0.0000';
                    $sanitizedRuns[$flatIdx++] = $item;
                }
            }
            $this->merge(['runs' => $sanitizedRuns]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'site_id' => ['nullable', 'exists:sites,id'],
            'prover_id' => ['nullable', 'exists:instruments,id'],
            'jauge_id' => ['nullable', 'exists:instruments,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'calibration_date' => ['required', 'date'],
            'reference_temperature' => ['required', 'numeric'],
            'pressure_unit' => ['required', 'string', 'in:bar,kPa,kpa'],
            'remarks' => ['nullable', 'string', 'max:1000'],

            'runs' => ['required', 'array', 'min:3'],
            'runs.*.run_number' => ['required', 'integer', 'min:1'],
            'runs.*.fill_number' => ['required', 'integer', 'min:1'],
            'runs.*.indicated_volume' => ['required', 'numeric', 'gt:0'],
            'runs.*.scale_reading_mm' => ['nullable', 'numeric'],
            'runs.*.gauge_temperature' => ['required', 'numeric'],
            'runs.*.prover_temperature' => ['required', 'numeric'],
            'runs.*.shaft_temperature' => ['nullable', 'numeric'],
            'runs.*.prover_pressure' => ['required', 'numeric', 'gte:0'],
        ];
    }

    public function toDTO(): ProverVerificationDTO
    {
        return ProverVerificationDTO::fromArray($this->validated());
    }
}
