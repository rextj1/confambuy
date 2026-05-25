<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Name of the user to register.',
                'example' => 'Jane Customer',
            ],
            'email' => [
                'description' => 'Unique email address for the user.',
                'example' => 'jane@example.com',
            ],
            'phone' => [
                'description' => 'Optional unique phone number.',
                'example' => '08012345678',
            ],
            'password' => [
                'description' => 'Password for the user account.',
                'example' => 'secret-password',
            ],
            'password_confirmation' => [
                'description' => 'Password confirmation that must match password.',
                'example' => 'secret-password',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A name is required.',
            'email.required' => 'An email address is required.',
            'email.email' => 'The email address must be valid.',
            'email.unique' => 'This email address is already registered.',
            'phone.unique' => 'This phone number is already registered.',
            'password.required' => 'A password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
