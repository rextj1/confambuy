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
