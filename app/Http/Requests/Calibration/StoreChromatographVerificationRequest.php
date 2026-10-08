<?php

declare(strict_types=1);

namespace App\Http\Requests\Calibration;

use Illuminate\Foundation\Http\FormRequest;

final class StoreChromatographVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instrument_id' => ['nullable', 'exists:instruments,id'],
            'report_mission_id' => ['nullable', 'exists:reports,id'],
            'mission_id' => ['nullable', 'exists:missions,id'],
            'verification_id' => ['nullable', 'exists:chromatograph_verifications,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'verification_date' => ['required', 'date'],
            'ambient_temperature' => ['nullable', 'numeric'],
            'ambient_pressure' => ['nullable', 'numeric'],

            'standard_gas_bottle_number' => ['required', 'string', 'max:100'],
            'certificate_number' => ['required', 'string', 'max:100'],
            'reference_conditions' => ['required', 'string', 'max:50'],
            'cylinder_validity_date' => ['nullable', 'date'],
            'cylinder_pressure_bar' => ['nullable', 'numeric'],

            'calibrator_ids' => ['nullable', 'array'],
            'calibrator_ids.*' => ['exists:equipment,id'],

            'components' => ['required', 'array', 'min:1'],
            'components.*.step_order' => ['required', 'integer'],
            'components.*.component_name' => ['required', 'string', 'max:50'],
            'components.*.component_symbol' => ['required', 'string', 'max:15'],
            'components.*.reference_value' => ['required', 'numeric'],
            'components.*.run_1' => ['required', 'numeric'],
            'components.*.run_2' => ['required', 'numeric'],
            'components.*.run_3' => ['required', 'numeric'],
            'components.*.run_4' => ['required', 'numeric'],
            'components.*.run_5' => ['required', 'numeric'],

            'physical_properties' => ['nullable', 'array'],
            'physical_properties.*.property_name' => ['required', 'string', 'max:100'],
            'physical_properties.*.property_symbol' => ['required', 'string', 'max:20'],
            'physical_properties.*.unit' => ['required', 'string', 'max:30'],
            'physical_properties.*.reference_value' => ['required', 'numeric'],
            'physical_properties.*.run_1' => ['required', 'numeric'],
            'physical_properties.*.run_2' => ['required', 'numeric'],
            'physical_properties.*.run_3' => ['required', 'numeric'],
            'physical_properties.*.run_4' => ['required', 'numeric'],
            'physical_properties.*.run_5' => ['required', 'numeric'],

            'remarks' => ['nullable', 'string'],
        ];
    }
}
