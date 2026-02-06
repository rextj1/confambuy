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
