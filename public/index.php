<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\App;
use App\Core\Request;
use App\Controller\AuthController;
use App\Middleware\AuthMiddleware;


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

// Auth Routes
$app->router->post('/api/auth/register', [new AuthController(), 'register']);
$app->router->post('/api/auth/login', [new AuthController(), 'login']);
$app->router->get('/api/auth/me', [new AuthController(), 'me'], [AuthMiddleware::class]);

// Dispatch - let the router handle the request
$app->run();
