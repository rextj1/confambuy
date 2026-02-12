<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCategoryRequest extends FormRequest
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
        $categoryId = $this->route('category')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($categoryId)],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
                Rule::notIn([$categoryId]),
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
                'description' => 'Updated category name.',
                'example' => 'Home Electronics',
            ],
            'slug' => [
                'description' => 'Updated URL slug.',
                'example' => 'home-electronics',
            ],
            'description' => [
                'description' => 'Updated category description.',
                'example' => 'TVs, speakers, and home automation devices.',
            ],
            'image' => [
                'description' => 'Updated image path or URL.',
                'example' => 'categories/home-electronics.jpg',
            ],
            'parent_id' => [
                'description' => 'Updated parent category ID.',
                'example' => 1,
            ],
            'is_active' => [
                'description' => 'Whether this category is active.',
                'example' => true,
            ],
            'sort_order' => [
                'description' => 'Updated display sort order.',
                'example' => 20,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.max' => 'The category slug may not be greater than 255 characters.',
            'slug.unique' => 'That category slug is already in use.',
            'parent_id.exists' => 'The selected parent category is invalid.',
            'parent_id.not_in' => 'A category cannot be its own parent.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('parent_id')) {
                return;
            }

            $categoryId = (int) ($this->route('category')?->id ?? 0);
            $newParentId = (int) $this->integer('parent_id');

            if ($categoryId === 0 || $newParentId === 0) {
                return;
            }

            if ($this->wouldCreateCycle($categoryId, $newParentId)) {
                $validator->errors()->add('parent_id', 'A category cannot be assigned to one of its descendants.');
            }
        });
    }

    private function wouldCreateCycle(int $categoryId, int $newParentId): bool
    {
        $currentId = $newParentId;
        $visited = [];

        while ($currentId > 0) {
            if ($currentId === $categoryId) {
                return true;
            }

            if (in_array($currentId, $visited, true)) {
                return false;
            }

            $visited[] = $currentId;

            $currentParentId = Category::query()
                ->withTrashed()
                ->whereKey($currentId)
                ->value('parent_id');

            if ($currentParentId === null) {
                return false;
            }

            $currentId = (int) $currentParentId;
        }

        return false;
    }
}
