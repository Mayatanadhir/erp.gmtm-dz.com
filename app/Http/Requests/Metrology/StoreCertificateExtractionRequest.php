<?php

declare(strict_types=1);

namespace App\Http\Requests\Metrology;

use App\Enums\EquipmentCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCertificateExtractionRequest extends FormRequest
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
            'certificate_file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'equipment_id' => [
                'nullable',
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
        ];
    }
}
