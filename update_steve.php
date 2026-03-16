<?php
$host = 'mysql-local';
$db = 'stevedb';
$user = 'root';
$pass = 'e7RptuJrfiEbSVHcY1gblq3DMSRwxASH';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $hash = password_hash('E2v#Steve!99', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE web_user SET username = ?, password = ? WHERE web_user_pk = 1");
    $stmt->execute(['e2vadm', $hash]);
    echo "SUCCESS: Steve Manager updated (e2vadm / E2v#Steve!99)\n";
} catch (\PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
