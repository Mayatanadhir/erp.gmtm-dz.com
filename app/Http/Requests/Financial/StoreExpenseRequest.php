<?php

declare(strict_types=1);

namespace App\Http\Requests\Financial;

use App\Enums\ExpenseAffiliation;
use App\Enums\ExpenseChargeType;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('date') && is_string($this->date) && str_contains($this->date, '/')) {
            try {
                $this->merge([
                    'date' => Carbon::createFromFormat('d/m/Y', trim($this->date))->format('Y-m-d'),
                ]);
            } catch (\Throwable) {
                // fall back to original input
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'type' => ['required', Rule::enum(ExpenseAffiliation::class)],
            'charge_type' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('type') === ExpenseAffiliation::Gmtm->value),
                Rule::enum(ExpenseChargeType::class),
            ],
            'description' => ['required', 'string', 'max:255'],
            'mission_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('type') === ExpenseAffiliation::Mission->value),
                'exists:missions,id',
            ],
            'contract_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('type') === ExpenseAffiliation::Contract->value),
                'exists:contracts,id',
            ],
            'attachment_item_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('type') === ExpenseAffiliation::Item->value),
                'exists:attachment_items,id',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount' => __('Amount'),
            'date' => __('Date'),
            'type' => __('Type'),
            'charge_type' => __('Charge Category'),
            'description' => __('Description'),
            'mission_id' => __('Mission'),
            'contract_id' => __('Contract'),
            'attachment_item_id' => __('Attachment Item'),
        ];
    }
}
