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
        'url' => env('WAHA_URL', env('WAHA_URL_1', 'http://localhost:3000')),
        'key' => trim(env('WAHA_API_KEY', '')),
        'webhook_base_url' => env('APP_URL', env('WAHA_WEBHOOK_BASE_URL', 'http://localhost')),
    ],
    'typebot' => [
        'url' => env('TYPEBOT_API_URL', 'http://typebot-viewer:3000'),
        'key' => env('TYPEBOT_API_KEY', 'typebot_secret_key_2026'),
    ],
];
