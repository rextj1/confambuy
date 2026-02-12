<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class PaystackInitializeRequest extends FormRequest
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
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'callback_url' => ['nullable', 'url'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'order_id' => [
                'description' => 'Order ID to initialize payment for.',
                'example' => 1,
            ],
            'callback_url' => [
                'description' => 'Optional URL to redirect the customer after payment.',
                'example' => 'https://confambuy.test/checkout/complete',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'An order is required.',
        ];
    }
}
