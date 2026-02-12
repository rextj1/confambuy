<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PricingQuoteRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku_id' => [
                'required',
                'integer',
                Rule::exists('product_skus', 'id')->whereNull('deleted_at'),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:255'],
            'shipping_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'shipping_method' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'items' => [
                'description' => 'List of line items to price.',
            ],
            'items.*.sku_id' => [
                'description' => 'Product SKU ID.',
                'example' => 10,
            ],
            'items.*.quantity' => [
                'description' => 'Requested quantity for the SKU.',
                'example' => 2,
            ],
            'coupon_code' => [
                'description' => 'Coupon code to apply.',
                'example' => 'WELCOME10',
            ],
            'shipping_address_id' => [
                'description' => 'Shipping address ID to use for shipping/tax estimation.',
                'example' => 4,
            ],
            'shipping_method' => [
                'description' => 'Selected shipping method.',
                'example' => 'standard',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'items.*.sku_id.required' => 'Each item must have a SKU.',
            'items.*.quantity.required' => 'Each item must have a quantity.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}
