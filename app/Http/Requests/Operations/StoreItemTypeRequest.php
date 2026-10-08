<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create article types') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'designation' => [
                'required',
                'string',
                'max:200',
                Rule::unique('item_types', 'designation'),
            ],
        ];
    }
}
