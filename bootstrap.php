<?php

declare(strict_types=1);

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Helper function untuk baca env dengan fallback ke getenv()
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

// Environment loading (hanya jika file .env ada, di Railway tidak ada)
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Timezone dari config
date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

// Error handling berdasarkan environment
$isDebug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
if ($isDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Exception handler - return JSON untuk API
set_exception_handler(function (Throwable $e) use ($isDebug) {
    http_response_code(500);
    header('Content-Type: application/json');
    
    $response = [
        'error' => 'INTERNAL_ERROR',
        'message' => $isDebug ? $e->getMessage() : 'Something went wrong',
    ];
    
    if ($isDebug) {
        $response['trace'] = $e->getTraceAsString();
    }
    
    echo json_encode($response);
    exit;
});
