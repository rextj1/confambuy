<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Support\ApiResponse;
use App\Support\ApiResponseCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Products
 *
 * Browse and manage products.
 */
class ProductController extends Controller
{
    public function __construct(private readonly ApiResponseCache $apiResponseCache)
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'featured']);
        $this->middleware('permission:products.create')->only('store');
        $this->middleware('permission:products.update')->only('update');
        $this->middleware('permission:products.delete')->only('destroy');
    }

    /**
     * List products.
     *
     * Returns a paginated list with filtering, sorting, and include options.
     *
     * @unauthenticated
     */
    public function index(): JsonResponse
    {
        $perPage = (int) request()->integer('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $products = QueryBuilder::for(Product::class)
            ->allowedFilters([
                'name',
                'price',
                AllowedFilter::exact('active'),
                AllowedFilter::exact('featured'),
                AllowedFilter::callback('category', function ($query, $value): void {
                    $query->whereHas('categories', function ($categoryQuery) use ($value): void {
                        $categoryQuery->where('slug', $value)
                            ->orWhere('name', $value)
                            ->orWhere('id', $value);
                    });
                }),
                AllowedFilter::callback('price_min', function ($query, $value): void {
                    $query->where('price', '>=', $value);
                }),
                AllowedFilter::callback('price_max', function ($query, $value): void {
                    $query->where('price', '<=', $value);
                }),
            ])
            ->allowedSorts(['price', 'name', 'created_at', 'published_at'])
            ->allowedIncludes(['categories'])
            ->defaultSort('-created_at')
            ->with(['categories', 'media', 'skus.media'])
            ->paginate($perPage);

        return ApiResponse::collection(ProductResource::collection($products));
    }

    /**
     * List featured products.
     *
     * Return a paginated list of active featured products.
     *
     * @unauthenticated
     */
    public function featured(): JsonResponse
    {
        $payload = $this->apiResponseCache->remember(
            prefix: 'api:v1:products:featured:index',
            tags: ['api:v1', 'products', 'featured'],
            queryParameters: request()->query(),
            resolver: function (): array {
                $perPage = (int) request()->integer('per_page', 15);
                $perPage = max(1, min($perPage, 100));

                $products = Product::query()
                    ->active()
                    ->featured()
                    ->with(['categories', 'media', 'skus.media'])
                    ->latest('published_at')
                    ->latest('id')
                    ->paginate($perPage);

                return ApiResponse::collection(ProductResource::collection($products))->getData(true);
            },
        );

        return response()->json($payload);
    }

    /**
     * Create a product.
     *
     * Create a new product record.
     *
     * @authenticated
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $categoryIds = collect($data['category_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();
        $categoryId = $data['category_id'] ?? null;

        if ($categoryIds->isEmpty() && $categoryId) {
            $categoryIds = collect([(int) $categoryId]);
        }

        $data['active'] = $data['is_active'] ?? true;

        unset($data['category_id'], $data['category_ids'], $data['is_active'], $data['stock_quantity']);

        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        $product = Product::create($data);

        if ($categoryIds->isNotEmpty()) {
            $product->categories()->sync($categoryIds->all());
            $this->flushProductRelatedCaches();
        }

        $product->load('categories');

        return ApiResponse::resource(new ProductResource($product), 201);
    }

    /**
     * Get a product.
     *
     * Fetch a single product with related media and categories.
     *
     * @unauthenticated
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['categories', 'media', 'skus.media']);

        return ApiResponse::resource(new ProductResource($product));
    }

    /**
     * Update a product.
     *
     * Update an existing product.
     *
     * @authenticated
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $categoryIds = collect($data['category_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();
        $categoryId = $data['category_id'] ?? null;

        if (array_key_exists('is_active', $data)) {
            $data['active'] = $data['is_active'];
        }

        unset($data['category_id'], $data['category_ids'], $data['is_active'], $data['stock_quantity']);

        if (empty($data['slug']) && array_key_exists('name', $data)) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $product->id);
        }

        $product->update($data);

        if ($request->exists('category_ids')) {
            $product->categories()->sync($categoryIds->all());
            $this->flushProductRelatedCaches();
        } elseif ($categoryId) {
            $product->categories()->sync([(int) $categoryId]);
            $this->flushProductRelatedCaches();
        }

        $product->load('categories');

        return ApiResponse::resource(new ProductResource($product));
    }

    /**
     * Delete a product.
     *
     * Soft delete a product.
     *
     * @authenticated
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return ApiResponse::message('Product deleted.');
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
        $query = Product::query()->where('slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    private function flushProductRelatedCaches(): void
    {
        $this->apiResponseCache->flushTags(['api:v1', 'products', 'featured'], ['api:v1', 'categories']);
    }
}
