<?php
$host = 'turntable.proxy.rlwy.net';
$port = 41979;
$db   = 'railway';
$user = 'root';
$pass = 'ZKmLHlKUPAZhZzDBJPzPxCSSDqTXQOVZ';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     $sql = file_get_contents('database/schema.sql');
     $pdo->exec($sql);
     echo 'Database seeded successfully! 🚀';
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
