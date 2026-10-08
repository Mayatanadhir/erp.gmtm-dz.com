<?php

namespace App\Http\Requests\Calibration;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransmitterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verification_date' => ['required', 'date'],
            'calibrator_1' => ['nullable', 'exists:equipment,id'],
            'calibrator_2' => ['nullable', 'exists:equipment,id'],
            'ambient_temperature' => ['nullable', 'numeric'],
            'ambient_pressure' => ['nullable', 'numeric'], // يمكن إضافة منطق معقد هنا إذا كان الضغط المطلق
            'points' => ['required', 'array', 'size:10'],
            'points.*.applied_percentage' => ['required', 'numeric'],
            'points.*.reference_value' => ['required', 'numeric'],
            'points.*.measured_signal' => ['nullable', 'numeric'],
            'points.*.indicated_value' => ['nullable', 'numeric'],
        ];
    }
}
