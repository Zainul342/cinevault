<?php
header('Content-Type: application/json');
try {
    echo json_encode([
        'status' => 'ok',
        'pdo_drivers' => PDO::getAvailableDrivers(),
        'loaded_extensions' => get_loaded_extensions(),
        'php_version' => PHP_VERSION,
        'sapi' => php_sapi_name(),
    ]);
} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
