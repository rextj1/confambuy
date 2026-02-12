<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        $productId = $this->route('product')?->id;

        return [
            'category_id' => [
                'nullable',
                'integer',
                'prohibits:category_ids',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
            'category_ids' => ['sometimes', 'array', 'prohibits:category_id'],
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'sku' => ['sometimes', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
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
                'example' => [3, 6],
            ],
            'name' => [
                'description' => 'Updated product name.',
                'example' => 'Wireless Mouse Pro',
            ],
            'slug' => [
                'description' => 'Updated product slug.',
                'example' => 'wireless-mouse-pro',
            ],
            'sku' => [
                'description' => 'Updated SKU code.',
                'example' => 'WM-001-PRO',
            ],
            'description' => [
                'description' => 'Updated product description.',
                'example' => 'Updated ergonomic wireless mouse with USB-C charging.',
            ],
            'price' => [
                'description' => 'Updated base price.',
                'example' => 17999.99,
            ],
            'compare_at_price' => [
                'description' => 'Updated original price before discount.',
                'example' => 21999.99,
            ],
            'featured' => [
                'description' => 'Whether to mark as featured.',
                'example' => true,
            ],
            'taxable' => [
                'description' => 'Whether tax should be applied.',
                'example' => true,
            ],
            'published_at' => [
                'description' => 'Updated publish date/time.',
                'example' => '2026-02-14 08:00:00',
            ],
            'metadata' => [
                'description' => 'Updated metadata values.',
                'example' => ['brand' => 'Logitech', 'edition' => 'pro'],
            ],
            'is_active' => [
                'description' => 'Whether the product is active.',
                'example' => true,
            ],
            'stock_quantity' => [
                'description' => 'Optional stock quantity field (if used by your flow).',
                'example' => 120,
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
            'name.string' => 'The product name must be a string.',
            'price.numeric' => 'The product price must be a number.',
            'slug.unique' => 'That product slug is already in use.',
            'sku.unique' => 'That product SKU is already in use.',
        ];
    }
}
