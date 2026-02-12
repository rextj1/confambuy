<?php

return [
    'enabled' => env('API_CACHE_ENABLED', true),
    'store' => env('API_CACHE_STORE', 'redis'),
    'ttl_seconds' => (int) env('API_CACHE_TTL_SECONDS', 300),
];
