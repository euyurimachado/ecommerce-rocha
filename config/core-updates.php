<?php

return [
    'package' => env('ECOMMERCE_CORE_PACKAGE', 'euyurimachado/ecommerce-core'),
    'default_channel' => env('ECOMMERCE_CORE_UPDATE_CHANNEL', 'stable'),
    'policy' => env('ECOMMERCE_CORE_UPDATE_POLICY', 'manual'),
    'cache_ttl_seconds' => (int) env('ECOMMERCE_CORE_UPDATE_CACHE_TTL', 86400),
    'request_timeout_seconds' => (int) env('ECOMMERCE_CORE_UPDATE_TIMEOUT', 8),

    'channels' => [
        'stable' => [
            'manifest_url' => env('ECOMMERCE_CORE_STABLE_MANIFEST_URL'),
        ],
        'beta' => [
            'manifest_url' => env('ECOMMERCE_CORE_BETA_MANIFEST_URL'),
        ],
    ],
];
