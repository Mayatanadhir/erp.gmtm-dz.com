<?php

declare(strict_types=1);

namespace App\Http\Requests\Attachment;

use App\Enums\BillingCycle;
use App\Models\ContractItem;
use App\Models\Mission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAttachmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'date' => ['required', 'date'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'mission_id' => ['required', 'exists:missions,id'],
            'ods' => ['nullable', 'string', 'max:45'],
            'code_ref' => ['nullable', 'string', 'max:45', 'unique:attachments,code_ref'],
            'type' => ['required', 'string', Rule::in(['service', 'supply'])],
            'status' => ['required', 'string', Rule::in(['draft', 'approved'])],
            'frequency' => ['nullable', 'string', Rule::enum(BillingCycle::class)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.contract_item_id' => ['required', 'distinct', 'exists:contract_items,id'],
            'items.*.actual_quantity' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'items.*.planned_quantity' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * Configure the validator instance with cross-field and domain integrity rules.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $items = $this->input('items', []);
            $hasPositiveQuantity = false;
            $contractItemIds = [];

            foreach ($items as $item) {
                $actual = (float) ($item['actual_quantity'] ?? 0);
                $planned = (float) ($item['planned_quantity'] ?? 0);

                if ($actual > 0 || $planned > 0) {
                    $hasPositiveQuantity = true;
                }

                if (! empty($item['contract_item_id'])) {
                    $contractItemIds[] = (int) $item['contract_item_id'];
                }
            }

            if (! $hasPositiveQuantity) {
                $v->errors()->add('items', __('At least one line item must have a quantity greater than zero.'));
            }

            // Resolve target contract to verify item ownership
            $missionId = $this->input('mission_id');
            $contractId = $this->input('contract_id');
            $mission = $missionId ? Mission::find($missionId) : null;
            $targetContractId = $contractId ? (int) $contractId : (int) ($mission?->contract_id ?? 0);

            if ($targetContractId > 0 && ! empty($contractItemIds)) {
                $validItemsCount = ContractItem::where('contract_id', $targetContractId)
                    ->whereIn('id', $contractItemIds)
                    ->count();

                if ($validItemsCount !== count(array_unique($contractItemIds))) {
                    $v->errors()->add('items', __('All line items must strictly belong to the associated contract.'));
                }
            }
        });
    }
}
