<?php

return [
    'paths' => ['api/*', 'webhook/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:3000'),
        env('MOBILE_URL', 'http://localhost:19006'),
        env('APP_URL'),
        'https://whatsapp-marketplace-mz.onrender.com',
        'https://marketplace-python.onrender.com',
        'http://localhost:8081',
    ]),

    'allowed_origins_patterns' => array_filter([
        env('CORS_PATTERN', ''),
    ]),

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'Retry-After',
    ],

    'max_age' => 86400,

    'supports_credentials' => true,
];
