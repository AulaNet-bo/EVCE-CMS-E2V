<?php

$host = 'localhost';
$db   = 'stevedb';
$user = 'steve';
$pass = 'changeme';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     $stmt = $pdo->prepare("UPDATE tariffs SET valid_until = '2026-05-30 23:59:59' WHERE id = 1");
     $stmt->execute();
     echo "Tariff ID 1 extended to 2026-05-30.\n";
} catch (\PDOException $e) {
     echo "Update failed: " . $e->getMessage() . "\n";
}
