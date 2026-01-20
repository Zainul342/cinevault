<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;

echo "Running migrations...\n";

try {
    $db = Database::getInstance();
    $sql = file_get_contents(__DIR__ . '/../database/schema.sql');
    
    // Split by ; to execute multiple statements (simple approach)
    // Note: PDO sometimes handles multiple statements in one go depending on driver/config
    // But safe to split for simple schema files
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => !empty($s)
    );

    foreach ($statements as $stmt) {
        echo "Executing: " . substr($stmt, 0, 50) . "...\n";
        $db->query($stmt);
    }

    echo "Migrations completed successfully!\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
