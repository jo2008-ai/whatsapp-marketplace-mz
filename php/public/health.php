<?php

// Health check endpoint for Railway
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use Illuminate\Support\Facades\DB;

header('Content-Type: application/json');

try {
    $dbOk = DB::getPdo()->query('SELECT 1')->fetch() !== false;
} catch (\Exception $e) {
    $dbOk = false;
}

$healthy = $dbOk;
http_response_code($healthy ? 200 : 503);

echo json_encode([
    'status' => $healthy ? 'ok' : 'degraded',
    'database' => $dbOk ? 'ok' : 'error',
]);
