<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('slug') && is_string($this->input('slug'))) {
            $normalizedSlug = Str::slug((string) $this->input('slug'));

            $this->merge([
                'slug' => $normalizedSlug !== '' ? $normalizedSlug : null,
            ]);
        }
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Category name.',
                'example' => 'Electronics',
            ],
            'slug' => [
                'description' => 'Custom URL slug. If omitted, it is auto-generated from the name.',
                'example' => 'electronics',
            ],
            'description' => [
                'description' => 'Category description.',
                'example' => 'Phones, laptops, accessories, and gadgets.',
            ],
            'image' => [
                'description' => 'Category image path or URL.',
                'example' => 'categories/electronics.jpg',
            ],
            'parent_id' => [
                'description' => 'Parent category ID for nested categories.',
                'example' => 2,
            ],
            'is_active' => [
                'description' => 'Whether this category is active.',
                'example' => true,
            ],
            'sort_order' => [
                'description' => 'Display sort order (lower appears first).',
                'example' => 10,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A category name is required.',
            'slug.max' => 'The category slug may not be greater than 255 characters.',
            'slug.unique' => 'That category slug is already in use.',
            'parent_id.exists' => 'The selected parent category is invalid.',
        ];
    }
}
