<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations;

use App\Models\Contract;
use App\Models\Mission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateMissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('edit missions') ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('chief_id') === '' || $this->input('chief_id') === '0') {
            $this->merge(['chief_id' => null]);
        }
        if ($this->input('contract_id') === '' || $this->input('contract_id') === '0') {
            $this->merge(['contract_id' => null]);
        }
        if ($this->input('vehicle_id') === '' || $this->input('vehicle_id') === '0') {
            $this->merge(['vehicle_id' => null]);
        }

        // Normalize equipments whether sent as array of scalar IDs or objects with id
        if ($this->has('equipments') && is_array($this->equipments)) {
            $normalizedEquipments = [];
            foreach ($this->equipments as $eq) {
                if (is_array($eq) && isset($eq['id'])) {
                    $normalizedEquipments[] = (int) $eq['id'];
                } elseif (is_numeric($eq)) {
                    $normalizedEquipments[] = (int) $eq;
                }
            }
            $this->merge(['equipments' => $normalizedEquipments]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'contract_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'mob_dmob_days' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'description' => ['nullable', 'string', 'max:2000'],
            'employees' => ['required', 'array', 'min:1'],
            'chief_id' => ['required', 'integer', 'exists:employees,id'],
            'equipments' => ['nullable', 'array'],
            'equipments.*' => ['integer', 'exists:equipment,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:equipment,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $employees = $this->input('employees', []);
            $chiefId = $this->input('chief_id');

            // If employees is array of objects with is_leader
            $leaderCount = 0;
            $allEmpIds = [];

            foreach ($employees as $emp) {
                if (is_array($emp)) {
                    $allEmpIds[] = (int) ($emp['id'] ?? 0);
                    if (! empty($emp['is_leader'])) {
                        $leaderCount++;
                    }
                } else {
                    $allEmpIds[] = (int) $emp;
                }
            }

            if (! $chiefId) {
                $v->errors()->add('chief_id', __('A mission leader must be designated. Please select a leader from the assigned employees.'));
            } elseif (! in_array((int) $chiefId, $allEmpIds, true)) {
                $v->errors()->add('chief_id', __('The designated mission leader must be one of the assigned team employees.'));
            }

            if ($leaderCount > 1) {
                $v->errors()->add('employees', __('A mission can only have one designated team leader.'));
            }

            if ($this->filled('contract_id')) {
                $missionParam = $this->route('mission') ?? $this->route('id');
                $currentContractId = null;
                if ($missionParam instanceof Mission) {
                    $currentContractId = $missionParam->contract_id;
                } elseif (is_numeric($missionParam)) {
                    $currentContractId = Mission::where('id', $missionParam)->value('contract_id');
                }

                if ((int) $this->input('contract_id') !== (int) $currentContractId) {
                    $contract = Contract::find($this->input('contract_id'));
                    if ($contract && ! $contract->isActive()) {
                        $v->errors()->add('contract_id', __('The selected contract has expired and cannot be used for missions.'));
                    }
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => __('The mission end date must be on or after the start date.'),
            'employees.required' => __('At least one field engineer must be assigned to the mission.'),
            'employees.min' => __('At least one field engineer must be assigned to the mission.'),
            'chief_id.required' => __('A mission leader must be designated. Please select a leader from the assigned employees.'),
        ];
    }
}
