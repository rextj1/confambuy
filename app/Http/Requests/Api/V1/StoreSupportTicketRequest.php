<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportTicketRequest extends FormRequest
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
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'category' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'order_id' => [
                'description' => 'Order ID associated with this ticket (if any).',
                'example' => 1,
            ],
            'category' => [
                'description' => 'Support issue category.',
                'example' => 'payment',
            ],
            'subject' => [
                'description' => 'Short summary of the issue.',
                'example' => 'Payment completed but order still pending',
            ],
            'description' => [
                'description' => 'Detailed explanation of the issue.',
                'example' => 'I completed payment and got a debit alert, but the order status is still pending.',
            ],
            'priority' => [
                'description' => 'Priority level: low, medium, high, or urgent.',
                'example' => 'high',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'A subject is required.',
            'description.required' => 'A description is required.',
        ];
    }
}
