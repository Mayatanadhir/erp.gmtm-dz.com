<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit article types') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $itemTypeId = $this->route('item_type')?->id;

        return [
            'designation' => [
                'required',
                'string',
                'max:200',
                Rule::unique('item_types', 'designation')->ignore($itemTypeId),
            ],
        ];
    }
}
