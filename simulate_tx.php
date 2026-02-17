<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Use Steve DB Connection
$db = DB::connection('steve');

$tag = 'AABBCCDD';
$connectorPk = 56; // SimulatedCP001 Connector 1 (Assuming 56 exists from previous checks)
$startValue = 1000;
$stopValue = 25000; // 24 kWh

$startTime = Carbon::now()->subHour();
$stopTime = Carbon::now();

try {
    echo "Simulating Transaction...\n";

    // 1. Insert into transaction_start
    $txId = $db->table('transaction_start')->insertGetId([
        'event_timestamp' => $startTime,
        'connector_pk' => $connectorPk,
        'id_tag' => $tag,
        'start_timestamp' => $startTime,
        'start_value' => $startValue
    ]);
    
    echo "Simulated Transaction START Created. TX ID: " . $txId . "\n";

    // 2. Insert into transaction_stop (to complete it)
    $db->table('transaction_stop')->insert([
        'transaction_pk' => $txId,
        'event_timestamp' => $stopTime,
        'event_actor' => 'manual',
        'stop_timestamp' => $stopTime,
        'stop_value' => $stopValue,
        'stop_reason' => 'Local'
    ]);
    
    echo "Simulated Transaction STOP Created. Energy: " . (($stopValue - $startValue) / 1000) . " kWh\n";
    
    // 3. Insert Meter Value (Log)
    $db->table('connector_meter_value')->insert([
        'connector_pk' => $connectorPk,
        'transaction_pk' => $txId,
        'value_timestamp' => $stopTime,
        'value' => $stopValue,
        'reading_context' => 'Transaction.End',
        'format' => 'Raw',
        'measurand' => 'Energy.Active.Import.Register',
        'location' => 'Outlet',
        'unit' => 'Wh'
    ]);
    
    echo "Added Meter Value Log.\n";
    
} catch (\Exception $e) {
    echo "Error simulating transaction: " . $e->getMessage() . "\n";
}
