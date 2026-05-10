<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function purgeCMS() {
    echo "Purging CMS...\n";
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    $cmsTables = ['charging_sessions', 'wallet_transactions', 'rfid_tags', 'remote_audit_logs'];
    foreach ($cmsTables as $t) {
        if (Schema::hasTable($t)) {
            echo "Truncating CMS table: $t\n";
            DB::table($t)->truncate();
        }
    }
    DB::table('wallets')->update(['balance' => 0]);
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "CMS Purged.\n";
}

function purgeSteve() {
    echo "Purging SteVe...\n";
    $conn = DB::connection('steve');
    $conn->statement('SET FOREIGN_KEY_CHECKS=0;');
    
    $tables = $conn->select('SHOW TABLES');
    foreach ($tables as $table) {
        $tableName = current((array)$table);
        
        $patterns = ['transaction', 'meter_value', 'ocpp_tag', 'tag_activity', 'reservation'];
        $match = false;
        foreach ($patterns as $p) {
            if (str_contains($tableName, $p)) {
                $match = true;
                break;
            }
        }

        if ($match) {
            try {
                echo "Truncating SteVe table: $tableName\n";
                $conn->table($tableName)->truncate();
            } catch (\Exception $e) {
                echo "Failed to truncate $tableName: " . $e->getMessage() . "\n";
            }
        }
    }

    $conn->statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "SteVe Purged.\n";
}

try {
    purgeCMS();
    purgeSteve();
    echo "SUCCESS: ALL_CLEANED\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
