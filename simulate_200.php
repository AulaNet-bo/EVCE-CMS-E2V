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
$connectorPk = 56; // SimulatedCP001 Connector 1
$startValue = 0;
$stopValue = 200; // 200 Units (kW/kWh as configured in CMS logic)

$startTime = Carbon::now()->subHour();
$stopTime = Carbon::now();

try {
    echo "Simulating 200 Unit Load...\n";

    // 1. Start
    $txId = $db->table('transaction_start')->insertGetId([
        'event_timestamp' => $startTime,
        'connector_pk' => $connectorPk,
        'id_tag' => $tag,
        'start_timestamp' => $startTime,
        'start_value' => $startValue
    ]);
    
    echo "Tx #$txId Started.\n";

    // 2. Stop
    $db->table('transaction_stop')->insert([
        'transaction_pk' => $txId,
        'event_timestamp' => $stopTime,
        'event_actor' => 'manual',
        'stop_timestamp' => $stopTime,
        'stop_value' => $stopValue,
        'stop_reason' => 'Local'
    ]);
    
    echo "Tx #$txId Stopped. Meter: $startValue -> $stopValue (Diff: 200)\n";
    
    // 3. Log
    $db->table('connector_meter_value')->insert([
        'connector_pk' => $connectorPk,
        'transaction_pk' => $txId,
        'value_timestamp' => $stopTime,
        'value' => $stopValue,
        'reading_context' => 'Transaction.End',
        'format' => 'Raw',
        'measurand' => 'Energy.Active.Import.Register',
        'location' => 'Outlet',
        'unit' => 'Wh' // Unit label in DB (CMS logic ignores this and uses raw value)
    ]);
    
    echo "Logs created.\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
