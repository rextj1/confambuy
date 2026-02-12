<?php

return [
    'currency' => 'NGN',
    'rounding' => [
        'precision' => 2,
        'mode' => 'round', // round, ceil, floor
    ],
    'tax_zones' => [
        'NG' => [
            'default' => env('PRICING_TAX_RATE_NG', 0.075),
            'states' => [
                'Lagos' => env('PRICING_TAX_RATE_NG_LAGOS', 0.075),
                'Abuja' => env('PRICING_TAX_RATE_NG_ABUJA', 0.075),
            ],
        ],
        'default' => env('PRICING_TAX_RATE', 0.0),
    ],
    'shipping' => [
        'default_carrier' => 'standard',
        'zones' => [
            [
                'name' => 'Nigeria',
                'countries' => ['Nigeria', 'NG'],
                'states' => ['Lagos', 'Abuja'],
                'carriers' => [
                    'standard' => [
                        'label' => 'Standard',
                        'rates' => [
                            ['max_weight' => 1, 'price' => 1000],
                            ['max_weight' => 5, 'price' => 1500],
                            ['max_weight' => 20, 'price' => 3000],
                        ],
                    ],
                    'express' => [
                        'label' => 'Express',
                        'rates' => [
                            ['max_weight' => 1, 'price' => 2000],
                            ['max_weight' => 5, 'price' => 3500],
                            ['max_weight' => 20, 'price' => 6000],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
