<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\App;
use App\Core\Request;

// Bootstrap application
$app = App::getInstance();

// Sample routes untuk testing Phase 1
$app->router->get('/', function (Request $req) {
    return [
        'name' => $_ENV['APP_NAME'] ?? 'CineVault',
        'version' => '1.0.0',
        'status' => 'running',
        'timestamp' => date('c'),
    ];
});

$app->router->get('/api/health', function (Request $req) {
    return [
        'status' => 'healthy',
        'environment' => $_ENV['APP_ENV'] ?? 'production',
    ];
});

// Dispatch - let the router handle the request
$app->run();
