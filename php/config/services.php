<?php

return [
    'python' => [
        'url' => env('PYTHON_URL', 'http://localhost:5000'),
    ],
    'admin' => [
        'key' => env('ADMIN_API_KEY', ''),
    ],
    'waha' => [
        'webhook_secret' => env('WAHA_WEBHOOK_SECRET', env('WEBHOOK_SECRET', '')),
        'url' => env('WAHA_URL_1', 'http://localhost:3000'),
        'urls' => [
            1 => env('WAHA_URL_1', env('WAHA_URL_1', 'http://localhost:3001')),
            2 => env('WAHA_URL_2', env('WAHA_URL_1', 'http://localhost:3002')),
            3 => env('WAHA_URL_3', env('WAHA_URL_1', 'http://localhost:3003')),
            4 => env('WAHA_URL_4', env('WAHA_URL_1', 'http://localhost:3004')),
        ],
        'key' => env('WAHA_API_KEY', ''),
    ],
];
