<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'category_id' => [
                'nullable',
                'integer',
                'prohibits:category_ids',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
            'category_ids' => ['nullable', 'array', 'prohibits:category_id'],
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'featured' => ['nullable', 'boolean'],
            'taxable' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'category_id' => [
                'description' => 'Single category ID for this product. Use either category_id or category_ids.',
                'example' => 3,
            ],
            'category_ids' => [
                'description' => 'Multiple category IDs. Use either category_id or category_ids.',
                'example' => [3, 5],
            ],
            'name' => [
                'description' => 'Product name.',
                'example' => 'Wireless Mouse',
            ],
            'slug' => [
                'description' => 'Custom product slug. Auto-generated if omitted.',
                'example' => 'wireless-mouse',
            ],
            'sku' => [
                'description' => 'Unique stock keeping unit code.',
                'example' => 'WM-001',
            ],
            'description' => [
                'description' => 'Product description.',
                'example' => 'Ergonomic 2.4GHz wireless mouse with silent clicks.',
            ],
            'price' => [
                'description' => 'Base product price.',
                'example' => 15999.99,
            ],
            'compare_at_price' => [
                'description' => 'Original price before discount.',
                'example' => 19999.99,
            ],
            'featured' => [
                'description' => 'Whether to mark the product as featured.',
                'example' => true,
            ],
            'taxable' => [
                'description' => 'Whether tax should be applied.',
                'example' => true,
            ],
            'published_at' => [
                'description' => 'Publish date/time.',
                'example' => '2026-02-12 10:00:00',
            ],
            'metadata' => [
                'description' => 'Additional product metadata.',
                'example' => ['brand' => 'Logitech', 'color' => 'black'],
            ],
            'is_active' => [
                'description' => 'Whether the product is active.',
                'example' => true,
            ],
            'stock_quantity' => [
                'description' => 'Optional stock quantity field (if used by your flow).',
                'example' => 100,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'The selected category is invalid.',
            'category_id.prohibits' => 'Provide either category_id or category_ids, not both.',
            'category_ids.prohibits' => 'Provide either category_id or category_ids, not both.',
            'category_ids.array' => 'The category IDs field must be an array.',
            'category_ids.*.exists' => 'One or more selected categories are invalid.',
            'name.required' => 'A product name is required.',
            'price.required' => 'A product price is required.',
            'price.numeric' => 'The product price must be a number.',
            'slug.unique' => 'That product slug is already in use.',
            'sku.unique' => 'That product SKU is already in use.',
        ];
    }
}
