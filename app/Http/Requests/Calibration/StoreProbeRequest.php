<?php

namespace App\Http\Requests\Calibration;

use Illuminate\Foundation\Http\FormRequest;

class StoreProbeRequest extends FormRequest
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
            'points' => ['required', 'array', 'size:5'],
            'points.*.reference_temperature' => ['required', 'numeric'],
            'points.*.measured_resistance' => ['required', 'numeric'],
            'points.*.indicated_temperature' => ['required', 'numeric'],
        ];
    }
}
