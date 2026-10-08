<?php

namespace App\Http\Requests\Calibration;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlowComputerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verification_date' => ['required', 'date'],
            'shunt_resistance' => ['nullable', 'numeric'],
            'calibrator_1' => ['nullable', 'exists:equipment,id'],
            'calibrator_2' => ['nullable', 'exists:equipment,id'],
            'transmitter_id' => ['required', 'exists:instruments,id'],
            'points' => ['required', 'array', 'size:10'],
            'points.*.applied_percentage' => ['required', 'numeric'],
            'points.*.expected_signal' => ['required', 'numeric'],
            'points.*.measured_signal' => ['required', 'numeric'],
            'points.*.expected_value' => ['required', 'numeric'],
            'points.*.indicated_value' => ['required', 'numeric'],
        ];
    }
}
