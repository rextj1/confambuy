<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportTicketMessageRequest extends FormRequest
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
            'message' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,txt,doc,docx'],
            'is_internal' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'message' => [
                'description' => 'Message content to append to the ticket thread.',
                'example' => 'Please see the payment reference attached.',
            ],
            'attachments' => [
                'description' => 'Optional attachment files.',
            ],
            'is_internal' => [
                'description' => 'Set true for internal staff-only notes.',
                'example' => false,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'A message is required.',
        ];
    }
}
