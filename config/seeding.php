<?php

return [
    'product' => [
        'categories_limit' => env('SEED_PRODUCT_CATEGORY_LIMIT'),
        'products_per_category' => (int) env('SEED_PRODUCTS_PER_CATEGORY', 6),
        'generate_media' => filter_var(env('SEED_PRODUCT_MEDIA', true), FILTER_VALIDATE_BOOL),
        'min_skus_per_product' => (int) env('SEED_MIN_SKUS_PER_PRODUCT', 1),
        'max_skus_per_product' => (int) env('SEED_MAX_SKUS_PER_PRODUCT', 3),
    ],
];
