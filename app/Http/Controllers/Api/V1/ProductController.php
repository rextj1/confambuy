<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
        $this->middleware('permission:products.create')->only('store');
        $this->middleware('permission:products.update')->only('update');
        $this->middleware('permission:products.delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $categoryId = $data['category_id'] ?? null;
        $data['active'] = $data['is_active'] ?? true;

        unset($data['category_id'], $data['is_active'], $data['stock_quantity']);

        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        $product = Product::create($data);

        if ($categoryId) {
            $product->categories()->attach($categoryId);
        }

        $product->load('categories');

        return ApiResponse::resource(new ProductResource($product), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['categories', 'media', 'skus.media']);

        return ApiResponse::resource(new ProductResource($product));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $categoryId = $data['category_id'] ?? null;

        if (array_key_exists('is_active', $data)) {
            $data['active'] = $data['is_active'];
        }

        unset($data['category_id'], $data['is_active'], $data['stock_quantity']);

        if (empty($data['slug']) && array_key_exists('name', $data)) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $product->id);
        }

        $product->update($data);

        if ($categoryId) {
            $product->categories()->sync([$categoryId]);
        }

        $product->load('categories');

        return ApiResponse::resource(new ProductResource($product));
    }

    /**
     * Remove the specified resource from storage.
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
}
