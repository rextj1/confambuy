<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartRequest extends FormRequest
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
            'coupon_code' => ['nullable', 'string', 'max:255'],
            'shipping_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'billing_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'shipping_method' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'coupon_code' => [
                'description' => 'Coupon code to apply or replace.',
                'example' => 'SAVE15',
            ],
            'shipping_address_id' => [
                'description' => 'Shipping address ID for this cart.',
                'example' => 4,
            ],
            'billing_address_id' => [
                'description' => 'Billing address ID for this cart.',
                'example' => 5,
            ],
            'shipping_method' => [
                'description' => 'Selected shipping method.',
                'example' => 'express',
            ],
        ];
    }
}
