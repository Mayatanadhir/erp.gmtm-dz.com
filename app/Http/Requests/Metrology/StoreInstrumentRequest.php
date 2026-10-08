<?php

declare(strict_types=1);

namespace App\Http\Requests\Metrology;

use App\Enums\AccuracyType;
use App\Enums\FluidType;
use App\Enums\InstrumentStatus;
use App\Enums\InstrumentType;
use App\Enums\ProcessVariable;
use App\Enums\ProverType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstrumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create measuring instruments') ?? false;
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        $type = $this->input('instrument_type');
        if (is_string($type)) {
            $normalizedType = InstrumentType::tryFromLegacy($type);
            if ($normalizedType) {
                $this->merge(['instrument_type' => $normalizedType->value]);
                $type = $normalizedType->value;
            }
        }

        if ($type === InstrumentType::Chromatograph->value) {
            $this->merge([
                'fluid_type' => FluidType::Gas->value,
                'process_variable' => ProcessVariable::Quality->value,
                'measurement_type' => 'Gas_Chromatography',
            ]);
        } elseif ($type === InstrumentType::StandardGauge->value) {
            $this->merge([
                'fluid_type' => FluidType::Liquid->value,
                'technology' => 'Conventional',
                'process_variable' => ProcessVariable::Volume->value,
                'measurement_type' => 'Volumetric_Standard',
            ]);
        } elseif ($type === InstrumentType::Prover->value) {
            $this->merge([
                'fluid_type' => FluidType::Liquid->value,
                'technology' => 'Conventional',
                'process_variable' => ProcessVariable::Volume->value,
                'measurement_type' => 'Volumetric_Displacement',
            ]);
        }

        if ($type !== InstrumentType::StandardGauge->value) {
            $this->merge(['standard_gauge_spec' => null]);
        }
        if ($type !== InstrumentType::Prover->value) {
            $this->merge(['prover_spec' => null]);
        }
        if ($type !== InstrumentType::Transmitter->value && $type !== InstrumentType::Probe->value) {
            $this->merge(['params' => null]);
        }
        if ($type !== InstrumentType::FlowComputer->value) {
            $this->merge(['transmitters' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'site_id' => ['nullable', 'integer', Rule::exists('sites', 'id')],
            'tag_number' => ['required', 'string', 'max:100'],
            'serial_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('instruments', 'serial_number')->withoutTrashed(),
            ],
            'instrument_type' => ['required', 'string', Rule::enum(InstrumentType::class)],
            'process_variable' => ['nullable', 'string', Rule::enum(ProcessVariable::class)],
            'measurement_type' => ['nullable', 'string', 'max:100'],
            'fluid_type' => ['nullable', 'string', Rule::enum(FluidType::class)],
            'technology' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', Rule::enum(InstrumentStatus::class)],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],

            // Physical parameters
            'params' => ['nullable', 'array'],
            'params.*.selected' => ['nullable', 'boolean'],
            'params.*.min' => ['nullable', 'numeric'],
            'params.*.max' => ['nullable', 'numeric'],
            'params.*.acc' => ['nullable', 'numeric'],
            'params.*.acc_type' => ['nullable', 'string', Rule::enum(AccuracyType::class)],

            // Transmitters linked to Flow Computer
            'transmitters' => ['nullable', 'array'],
            'transmitters.*.channel' => ['nullable', 'string', 'max:50'],
            'transmitters.*.channel_number' => ['nullable', 'string', 'max:50'],
            'transmitters.*.id' => ['nullable', 'integer', Rule::exists('instruments', 'id')],
            'transmitters.*.transmitter_id' => ['nullable', 'integer', Rule::exists('instruments', 'id')],

            // Standard gauge specs
            'standard_gauge_spec' => ['nullable', 'array'],
            'standard_gauge_spec.nominal_capacity_liters' => ['required_if:instrument_type,'.InstrumentType::StandardGauge->value, 'nullable', 'numeric', 'min:0.0001'],
            'standard_gauge_spec.neck_scale_sensitivity' => ['nullable', 'numeric', 'min:0.00001'],
            'standard_gauge_spec.cubical_expansion_coef_gcm' => ['nullable', 'numeric'],
            'standard_gauge_spec.vessel_material' => ['nullable', 'string', 'max:100'],
            'standard_gauge_spec.base_reference_temperature' => ['nullable', 'numeric'],
            'standard_gauge_spec.calibration_certificate_number' => ['nullable', 'string', 'max:100'],
            'standard_gauge_spec.calibration_date' => ['nullable', 'date'],
            'standard_gauge_spec.calibration_expiry_date' => ['nullable', 'date'],

            // Prover specs
            'prover_spec' => ['nullable', 'array'],
            'prover_spec.type' => ['required_if:instrument_type,'.InstrumentType::Prover->value, 'nullable', 'string', Rule::enum(ProverType::class)],
            'prover_spec.inner_diameter' => ['nullable', 'numeric', 'min:0.1'],
            'prover_spec.wall_thickness' => ['nullable', 'numeric', 'min:0.1'],
            'prover_spec.nominal_base_volume' => ['nullable', 'numeric', 'min:0'],
            'prover_spec.cubical_expansion_coef' => ['nullable', 'numeric'],
            'prover_spec.elasticity_modulus' => ['nullable', 'numeric'],
            'prover_spec.area_expansion_coef' => ['nullable', 'numeric'],
            'prover_spec.linear_expansion_coef' => ['nullable', 'numeric'],
            'prover_spec.material' => ['nullable', 'string', 'max:100'],
            'prover_spec.pulse_interpolation' => ['nullable', 'boolean'],
        ];
    }
}
