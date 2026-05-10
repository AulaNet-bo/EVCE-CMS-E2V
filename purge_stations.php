<?php
$pdo = new PDO('mysql:host=mysql-local;dbname=stevedb', 'steve', 'changeme');

// 1. Get real charge boxes from Steve
$stmt = $pdo->query("SELECT charge_box_id FROM charge_box");
$realIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($realIds)) {
    die("Error: No charge boxes found in Steve DB.\n");
}

$inClause = "'" . implode("','", $realIds) . "'";

// 2. Deactivate stations in CMS that are not in Steve
$deactivated = $pdo->exec("UPDATE stations SET is_active = 0 WHERE charge_box_id NOT IN ($inClause)");
echo "Deactivated $deactivated orphaned stations in CMS.\n";

// 3. Activate and Assign new stations to Las Palmas (ID 1)
$updated = $pdo->exec("UPDATE stations SET is_active = 1, location_id = 1 WHERE location_id IS NULL OR location_id = 0 OR charge_box_id IN ($inClause)");
echo "Activated/Located $updated real stations.\n";

echo "Database Sane and Sincronized.\n";
