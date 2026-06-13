<?php

return [
    'python' => [
        'url' => env('PYTHON_URL', 'http://localhost:5000'),
    ],
    'evolution' => [
        'url' => env('EVOLUTION_URL', 'http://localhost:8080'),
        'key' => env('EVOLUTION_API_KEY', ''),
        'webhook_secret' => env('EVOLUTION_WEBHOOK_SECRET'),
    ],
];
