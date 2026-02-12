<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCategoryRequest;
use App\Http\Requests\Api\V1\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\ApiResponse;
use App\Support\ApiResponseCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Categories
 *
 * Browse and manage product categories.
 */
class CategoryController extends Controller
{
    public function __construct(private readonly ApiResponseCache $apiResponseCache)
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
        $this->middleware('permission:categories.manage')->only(['store', 'update', 'destroy']);
    }

    /**
     * List categories.
     *
     * Return a paginated list of categories.
     *
     * @unauthenticated
     */
    public function index(): JsonResponse
    {
        $payload = $this->apiResponseCache->remember(
            prefix: 'api:v1:categories:index',
            tags: ['api:v1', 'categories'],
            queryParameters: request()->query(),
            resolver: function (): array {
                $perPage = (int) request()->integer('per_page', 15);
                $perPage = max(1, min($perPage, 100));

                $categories = QueryBuilder::for(Category::query())
                    ->allowedFilters([
                        'name',
                        'slug',
                        AllowedFilter::exact('is_active'),
                        AllowedFilter::exact('parent_id'),
                    ])
                    ->allowedSorts(['name', 'sort_order', 'created_at'])
                    ->allowedIncludes(['parent', 'children', 'products'])
                    ->defaultSort('sort_order')
                    ->with('parent')
                    ->paginate($perPage);

                return ApiResponse::collection(CategoryResource::collection($categories))->getData(true);
            },
        );

        return response()->json($payload);
    }

    /**
     * Create a category.
     *
     * Create a new category record.
     *
     * @authenticated
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        $category = Category::query()->create($data);
        $category->load('parent');

        return ApiResponse::resource(new CategoryResource($category), 201);
    }

    /**
     * Get a category.
     *
     * Fetch a single category.
     *
     * @unauthenticated
     */
    public function show(Category $category): JsonResponse
    {
        $payload = $this->apiResponseCache->remember(
            prefix: "api:v1:categories:show:{$category->id}",
            tags: ['api:v1', 'categories'],
            queryParameters: request()->query(),
            resolver: function () use ($category): array {
                $cachedCategory = QueryBuilder::for(Category::query())
                    ->allowedIncludes(['parent', 'children', 'products'])
                    ->whereKey($category->id)
                    ->with('parent')
                    ->firstOrFail();

                return ApiResponse::resource(new CategoryResource($cachedCategory))->getData(true);
            },
        );

        return response()->json($payload);
    }

    /**
     * Update a category.
     *
     * Update an existing category.
     *
     * @authenticated
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['slug']) && array_key_exists('name', $data)) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $category->id);
        }

        $category->update($data);
        $category->load('parent');

        return ApiResponse::resource(new CategoryResource($category));
    }

    /**
     * Delete a category.
     *
     * Soft delete a category.
     *
     * @authenticated
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return ApiResponse::message('Category deleted.');
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = Category::query()
            ->withTrashed()
            ->where('slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
