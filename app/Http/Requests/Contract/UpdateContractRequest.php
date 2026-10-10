<?php

declare(strict_types=1);

namespace App\Http\Requests\Contract;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractRequest extends FormRequest
{
    /**
     * Authorization is handled by route middleware (permission:edit contracts).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('warranty_id') && ! $this->has('garantie_id')) {
            $this->merge([
                'garantie_id' => $this->input('warranty_id'),
            ]);
        }
    }

    /**
     * Validation rules for updating an existing contract.
     * Reference uniqueness ignores the current contract's own ID.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $contractId = $this->route('contract')?->id
            ?? $this->route('id');

        return [
            'reference' => ['required', 'string', 'max:110', Rule::unique('contracts', 'reference')->ignore($contractId)],
            'object' => ['nullable', 'string', 'max:200'],
            'date_signature' => ['nullable', 'date', 'required_with:duree'],
            'duree' => ['nullable', 'integer', 'min:1', 'max:600', 'required_with:date_signature'],
            'montant_global_prevu' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'garantie_id' => ['nullable', 'exists:garanties,id', Rule::unique('contracts', 'garantie_id')->ignore($contractId)],

            // Contract items (existing items have an id field)
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer', 'exists:contract_items,id'],
            'items.*.item_type_id' => ['nullable', 'exists:item_types,id'],
            'items.*.designation' => ['required_with:items', 'string', 'max:200'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'items.*.type' => ['nullable', 'string', Rule::in(['service', 'supply'])],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'items.*.frequency' => ['nullable', 'string', Rule::in(['annuelle', 'semestrielle'])],
        ];
    }

    /**
     * Custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reference.required' => __('validation.required', ['attribute' => __('Reference')]),
            'reference.unique' => __('validation.unique', ['attribute' => __('Reference')]),
            'items.*.designation.required_with' => __('validation.required', ['attribute' => __('Item designation')]),
        ];
    }
}
