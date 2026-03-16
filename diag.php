<?php
$results = [];

// 1. Connection check
$start = microtime(true);
try {
    $pdo = new PDO('mysql:host=host.docker.internal;port=3306;dbname=stevedb', 'steve', 'changeme');
    $results['pdo_connect'] = (microtime(true) - $start) . 's';
} catch (Exception $e) {
    $results['pdo_connect_error'] = $e->getMessage();
}

// 2. Query check
$start = microtime(true);
$pdo->query('SELECT 1');
$results['simple_query'] = (microtime(true) - $start) . 's';

// 3. Redis check
$start = microtime(true);
try {
    $redis = new Redis();
    $redis->connect('redis', 6379);
    $redis->ping();
    $results['redis_ping'] = (microtime(true) - $start) . 's';
} catch (Exception $e) {
    $results['redis_error'] = $e->getMessage();
}

print_r($results);
