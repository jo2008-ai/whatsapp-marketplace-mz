<?php

return [
    'python' => [
        'url' => env('PYTHON_URL', 'http://localhost:5000'),
    ],
    'waha' => [
        'url' => env('WAHA_URL', 'http://localhost:3000'),
        'key' => env('WAHA_API_KEY', ''),
    ],
];
