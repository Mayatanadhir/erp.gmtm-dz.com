<?php

declare(strict_types=1);

namespace App\Http\Requests\Analytics;

use App\Models\IncomeForecast;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateForecastRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('edit annual forecasts') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $forecast = $this->route('forecast');
        $forecastId = $forecast instanceof IncomeForecast ? $forecast->id : (int) $forecast;

        return [
            'year' => ['required', 'integer', 'between:2000,2100', 'unique:income_forecasts,year,'.$forecastId],
            'expected_work_days' => ['required', 'integer', 'between:1,366'],
            'annual_income' => ['required', 'numeric', 'min:0'],
        ];
    }
}
