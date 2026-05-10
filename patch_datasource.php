<?php
$serviceFile = 'app/Services/SteveDataSource.php';
$content = file_get_contents($serviceFile);

$newMethod = '
    public function getLatestChargeBoxStatus(string $chargeBoxId): ?object
    {
        if (!$this->usingRedis()) {
            return DB::connection(\'steve\')->table(\'charge_box\')
                ->where(\'charge_box_id\', $chargeBoxId)
                ->first();
        }

        $prefix = $this->redisPrefix();
        $row = $this->normalizeRedisRow($this->redis()->hgetall("{$prefix}:charge_box:{$chargeBoxId}"));
        return $row ? (object) $row : null;
    }
';

if (strpos($content, 'getLatestChargeBoxStatus') === false) {
    $content = str_replace('public function getConnectorsWithStatus()', $newMethod . "\n    public function getConnectorsWithStatus()", $content);
    file_put_contents($serviceFile, $content);
    echo "Method getLatestChargeBoxStatus added successfully.\n";
} else {
    echo "Method already exists.\n";
}

// 2. Fix GPS for Virtual Laboratory
try {
    $pdo = new PDO('mysql:host=mysql-local;dbname=stevedb', 'steve', 'changeme');
    // Using SC Santa Cruz coordinates for Virtual Lab
    $pdo->exec("UPDATE locations SET latitude = -17.7554, longitude = -63.1784 WHERE id = 2 AND (latitude IS NULL OR latitude = 0)");
    echo "GPS coordinates updated for Virtual Laboratory.\n";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
