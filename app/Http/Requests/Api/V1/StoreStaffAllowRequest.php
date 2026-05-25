<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffAllowRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $email = (string) $this->input('email', '');

        $this->merge([
            'email' => strtolower(trim($email)),
        ]);
    }

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
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('staff_allows', 'email'),
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'Staff email to allow at registration.',
                'example' => 'staff@confambuy.com',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'A staff email is required.',
            'email.email' => 'The staff email must be valid.',
            'email.unique' => 'This email is already on the staff allowlist.',
        ];
    }
}
