<?php

declare(strict_types=1);

namespace App\Http\Requests\Metrology;

use App\Enums\EquipmentCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalibrationCertificateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create calibration certificates') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:100'],
            'certificate_type' => ['nullable', 'string', 'in:initial,periodic,after_repair,intermediate'],
            'equipment_id' => [
                'required',
                'integer',
                Rule::exists('equipment', 'id')->where(function ($query): void {
                    $query->where(function ($q): void {
                        $q->where('requires_calibration', true)
                            ->orWhere('category', EquipmentCategory::MeasuringInstrument->value);
                    })->whereNotIn('category', [
                        EquipmentCategory::WorkTool->value,
                        EquipmentCategory::Vehicle->value,
                    ]);
                }),
            ],
            'laboratory_name' => ['nullable', 'string', 'max:255'],
            'calibration_date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:calibration_date'],
            'validity_period_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'certificate_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'environmental_conditions' => ['nullable', 'array'],
            'environmental_conditions.temperature_celsius' => ['nullable', 'numeric'],
            'environmental_conditions.humidity_percent' => ['nullable', 'numeric', 'between:0,100'],
            'environmental_conditions.atmospheric_pressure_hpa' => ['nullable', 'numeric'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'points' => ['nullable', 'array'],
            'points.*.nominal_value' => ['required_with:points', 'numeric'],
            'points.*.correction' => ['required_with:points', 'numeric'],
            'points.*.uncertainty' => ['required_with:points', 'numeric', 'min:0'],
            'points.*.equipment_specification_id' => ['nullable', 'integer', 'exists:equipment_specifications,id'],
        ];
    }
}
