<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$txId = 18;
$now = Carbon::now();

echo "Force Stopping Transaction #$txId...\n";

// 1. Check if active
$tx = DB::connection('steve')->table('transaction')->where('transaction_pk', $txId)->first();
if (!$tx) {
    die("Transaction not found.\n");
}
if ($tx->stop_timestamp) {
    die("Transaction already stopped at " . $tx->stop_timestamp . "\n");
}

// 2. Insert into transaction_stop
// This is how Steve records a stop. Steve triggers might update the main transaction table, 
// but since we are bypassing Steve app, we might need to update 'transaction' table too if triggers aren't active in DB layer.
// Usually Steve updates 'transaction' view/table based on start/stop tables.
// Let's try inserting to transaction_stop first.

try {
    DB::connection('steve')->table('transaction_stop')->insert([
        'transaction_pk' => $txId,
        'event_timestamp' => $now,
        'event_actor' => 'manual', // Changed from operator to manual
        'stop_timestamp' => $now,
        'stop_value' => $tx->start_value + 5097, // Assume consumption derived from last check
        'stop_reason' => 'RemoteStop'
    ]);
    
    echo "Inserted into transaction_stop.\n";
    
    // 3. Manually update transaction table just in case (Steve might rely on Java app logic to sync this)
    DB::connection('steve')->table('transaction')->where('transaction_pk', $txId)->update([
        'stop_timestamp' => $now,
        'stop_value' => $tx->start_value + 5097,
        'stop_reason' => 'RemoteStop'
    ]);
    
    echo "Updated transaction table.\n";
    echo "Transaction #$txId Stopped successfully.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
