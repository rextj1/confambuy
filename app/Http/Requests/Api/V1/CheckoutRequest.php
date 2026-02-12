<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
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
            'shipping_address_id' => ['required', 'integer', 'exists:addresses,id'],
            'billing_address_id' => ['required', 'integer', 'exists:addresses,id'],
            'shipping_method' => ['required', 'string', 'max:50'],
            'payment_method' => ['required', 'string', 'max:50'],
            'idempotency_key' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'shipping_address_id' => [
                'description' => 'Shipping address ID.',
                'example' => 4,
            ],
            'billing_address_id' => [
                'description' => 'Billing address ID.',
                'example' => 5,
            ],
            'shipping_method' => [
                'description' => 'Shipping method code.',
                'example' => 'standard',
            ],
            'payment_method' => [
                'description' => 'Payment method code.',
                'example' => 'paystack',
            ],
            'idempotency_key' => [
                'description' => 'Unique key for safe retries. This is read from the Idempotency-Key header.',
                'example' => 'checkout-user-42-20260212-001',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function headers(): array
    {
        return [
            'Idempotency-Key' => [
                'description' => 'Required unique request key used to prevent duplicate order creation.',
                'example' => 'checkout-user-42-20260212-001',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_address_id.required' => 'A shipping address is required.',
            'billing_address_id.required' => 'A billing address is required.',
            'shipping_method.required' => 'A shipping method is required.',
            'payment_method.required' => 'A payment method is required.',
            'idempotency_key.required' => 'An idempotency key is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => trim((string) $this->header('Idempotency-Key')),
        ]);
    }
}
