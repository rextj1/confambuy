<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'line_1' => ['required', 'string', 'max:255'],
            'line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'default_shipping' => ['nullable', 'boolean'],
            'default_billing' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Recipient full name.',
                'example' => 'John Doe',
            ],
            'email' => [
                'description' => 'Recipient email address.',
                'example' => 'john@example.com',
            ],
            'line_1' => [
                'description' => 'Primary street address line.',
                'example' => '12 Admiralty Way',
            ],
            'line_2' => [
                'description' => 'Secondary address details (optional).',
                'example' => 'Lekki Phase 1',
            ],
            'city' => [
                'description' => 'City.',
                'example' => 'Lagos',
            ],
            'state' => [
                'description' => 'State or province.',
                'example' => 'Lagos',
            ],
            'postal_code' => [
                'description' => 'Postal or ZIP code.',
                'example' => '100001',
            ],
            'country' => [
                'description' => 'Country name or code.',
                'example' => 'Nigeria',
            ],
            'phone' => [
                'description' => 'Recipient phone number.',
                'example' => '+2348012345678',
            ],
            'default_shipping' => [
                'description' => 'Mark as the default shipping address.',
                'example' => true,
            ],
            'default_billing' => [
                'description' => 'Mark as the default billing address.',
                'example' => false,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'line_1.required' => 'The address line 1 field is required.',
            'city.required' => 'The city field is required.',
            'phone.required' => 'The phone field is required.',
            'email.email' => 'The email address must be valid.',
        ];
    }
}
