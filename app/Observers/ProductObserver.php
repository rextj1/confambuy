<?php

namespace App\Observers;

use App\Models\Product;
use App\Support\ApiResponseCache;

class ProductObserver
{
    public function __construct(private readonly ApiResponseCache $apiResponseCache) {}

    public function saved(Product $product): void
    {
        $this->flushProductRelatedCaches();
    }

    public function deleted(Product $product): void
    {
        $this->flushProductRelatedCaches();
    }

    public function restored(Product $product): void
    {
        $this->flushProductRelatedCaches();
    }

    public function forceDeleted(Product $product): void
    {
        $this->flushProductRelatedCaches();
    }

    private function flushProductRelatedCaches(): void
    {
        $this->apiResponseCache->flushTags(['api:v1', 'products', 'featured'], ['api:v1', 'categories']);
    }
}
