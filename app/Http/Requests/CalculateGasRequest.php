<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateGasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pressure' => ['required', 'numeric', 'gt:0'],
            'pressure_unit' => ['nullable', 'string', 'in:kpa,bar,psi,mpa,atm,kPa,BAR,PSI,MPa,ATM'],
            'temperature' => ['required', 'numeric'],
            'temperature_unit' => ['nullable', 'string', 'in:k,c,f,kelvin,celsius,fahrenheit,K,C,F'],
            'composition' => ['required', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pressure.required' => __('Pressure is required.'),
            'pressure.gt' => __('Pressure must be greater than zero.'),
            'temperature.required' => __('Temperature is required.'),
            'composition.required' => __('Gas composition is required.'),
            'composition.array' => __('Gas composition must be an array or object.'),
        ];
    }
}
