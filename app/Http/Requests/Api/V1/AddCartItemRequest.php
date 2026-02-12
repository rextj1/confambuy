<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddCartItemRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku_id' => [
                'required',
                'integer',
                Rule::exists('product_skus', 'id')->whereNull('deleted_at'),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'sku_id' => [
                'description' => 'Product SKU ID to add to cart.',
                'example' => 12,
            ],
            'quantity' => [
                'description' => 'Quantity to add.',
                'example' => 1,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'sku_id.required' => 'A SKU is required.',
            'quantity.required' => 'A quantity is required.',
        ];
    }
}
