<?php

namespace App\Support;

use Closure;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ApiResponseCache
{
    private static bool $cacheStoreUnavailable = false;

    /**
     * @param  array<int, string>  $tags
     * @param  array<string, mixed>  $queryParameters
     */
    public function remember(string $prefix, array $tags, array $queryParameters, Closure $resolver): array
    {
        if (! $this->shouldUseCache()) {
            return $resolver();
        }

        try {
            $cache = $this->cacheRepository();
            $ttl = now()->addSeconds(max((int) config('api_cache.ttl_seconds', 300), 1));
            $key = $prefix.':'.md5(http_build_query(Arr::sortRecursive($queryParameters)));

            if ($cache->getStore() instanceof TaggableStore) {
                return $cache->tags($tags)->remember($key, $ttl, $resolver);
            }

            return $cache->remember($key, $ttl, $resolver);
        } catch (Throwable) {
            $this->markCacheStoreUnavailable();

            return $resolver();
        }
    }

    /**
     * @param  array<int, string>  ...$tagSets
     */
    public function flushTags(array ...$tagSets): void
    {
        if (! $this->shouldUseCache()) {
            return;
        }

        try {
            $cache = $this->cacheRepository();

            if (! ($cache->getStore() instanceof TaggableStore)) {
                return;
            }

            foreach ($tagSets as $tagSet) {
                $cache->tags($tagSet)->flush();
            }
        } catch (Throwable) {
            $this->markCacheStoreUnavailable();

            return;
        }
    }

    private function cacheRepository(): CacheRepository
    {
        return Cache::store((string) config('api_cache.store', 'redis'));
    }

    private function shouldUseCache(): bool
    {
        if (! config('api_cache.enabled', true)) {
            return false;
        }

        if ((string) config('api_cache.store', 'redis') !== 'redis') {
            return true;
        }

        return ! self::$cacheStoreUnavailable;
    }

    private function markCacheStoreUnavailable(): void
    {
        if ((string) config('api_cache.store', 'redis') === 'redis') {
            self::$cacheStoreUnavailable = true;
        }
    }
}
