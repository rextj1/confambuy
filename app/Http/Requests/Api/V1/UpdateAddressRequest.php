<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
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
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'line_1' => ['sometimes', 'string', 'max:255'],
            'line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:30'],
            'default_shipping' => ['sometimes', 'boolean'],
            'default_billing' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Updated recipient full name.',
                'example' => 'John Doe',
            ],
            'email' => [
                'description' => 'Updated recipient email address.',
                'example' => 'john@example.com',
            ],
            'line_1' => [
                'description' => 'Updated primary street address line.',
                'example' => '24 Bourdillon Road',
            ],
            'line_2' => [
                'description' => 'Updated secondary address details.',
                'example' => 'Ikoyi',
            ],
            'city' => [
                'description' => 'Updated city.',
                'example' => 'Lagos',
            ],
            'state' => [
                'description' => 'Updated state or province.',
                'example' => 'Lagos',
            ],
            'postal_code' => [
                'description' => 'Updated postal or ZIP code.',
                'example' => '101241',
            ],
            'country' => [
                'description' => 'Updated country name or code.',
                'example' => 'Nigeria',
            ],
            'phone' => [
                'description' => 'Updated recipient phone number.',
                'example' => '+2348098765432',
            ],
            'default_shipping' => [
                'description' => 'Mark as the default shipping address.',
                'example' => false,
            ],
            'default_billing' => [
                'description' => 'Mark as the default billing address.',
                'example' => true,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'The email address must be valid.',
        ];
    }
}
