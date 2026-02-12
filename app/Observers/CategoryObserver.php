<?php

namespace App\Observers;

use App\Models\Category;
use App\Support\ApiResponseCache;

class CategoryObserver
{
    public function __construct(private readonly ApiResponseCache $apiResponseCache) {}

    public function saved(Category $category): void
    {
        $this->flushCategoryRelatedCaches();
    }

    public function deleted(Category $category): void
    {
        $this->flushCategoryRelatedCaches();
    }

    public function restored(Category $category): void
    {
        $this->flushCategoryRelatedCaches();
    }

    public function forceDeleted(Category $category): void
    {
        $this->flushCategoryRelatedCaches();
    }

    private function flushCategoryRelatedCaches(): void
    {
        $this->apiResponseCache->flushTags(['api:v1', 'categories'], ['api:v1', 'products', 'featured']);
    }
}
