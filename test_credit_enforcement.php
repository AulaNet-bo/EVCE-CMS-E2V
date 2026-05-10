<?php
putenv('DB_HOST=127.0.0.1');
$_ENV['DB_HOST'] = '127.0.0.1';
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=array');
putenv('BROADCAST_CONNECTION=log');
putenv('REDIS_CLIENT=mock'); // To avoid phpredis initialization

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
config(['database.connections.mysql.host' => '127.0.0.1']);
config(['database.connections.steve.host' => '127.0.0.1']);
// Override data source to mysql because local PHP lacks Redis extension
config(['steve.source' => 'mysql']);
config(['steve.force_redis' => false]);
putenv('STEVE_DATA_SOURCE=mysql');
$_ENV['STEVE_DATA_SOURCE'] = 'mysql';
// Also putenv for any other lookups
putenv('STEVE_DB_HOST=127.0.0.1');
$_ENV['STEVE_DB_HOST'] = '127.0.0.1';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\RfidTag;
use App\Models\Station;
use App\Models\ChargingSession;
use App\Console\Commands\MonitorActiveTransactions;
use Carbon\Carbon;

echo "--- CREDIT ENFORCEMENT TEST ---\n";

// 1. Prepare User
$u = User::find(1);
$u->wallet->update(['balance' => 2.00]);
$tag = RfidTag::where('user_id', $u->id)->first();
echo "User: {$u->name} | Balance: {$u->balance} | Tag: {$tag->tag_code}\n";

// 2. Prepare Station
$db = DB::connection('steve');
$steveConnector = $db->table('connector')->first();
if (!$steveConnector) die("No connectors found in SteVe DB.\n");

$connectorPk = $steveConnector->connector_pk;
$chargeBoxId = $steveConnector->charge_box_id;

echo "Using SteVe Connector PK: $connectorPk | ChargeBox: $chargeBoxId\n";

$station = Station::where('charge_box_id', $chargeBoxId)->first();
if (!$station) {
    echo "Station $chargeBoxId not found in CMS, creating it...\n";
    $station = Station::create([
        'charge_box_id' => $chargeBoxId,
        'name' => "Station $chargeBoxId",
        'is_active' => true,
        'tariff_id' => 1
    ]);
}

// 3. Start Transaction in SteVe
echo "Ensuring tag exists in SteVe DB...\n";
$db = DB::connection('steve');
$db->table('ocpp_tag')->updateOrInsert(
    ['id_tag' => $tag->tag_code],
    ['expiry_date' => Carbon::now()->addYear(), 'parent_id_tag' => null]
);

echo "Starting Transaction in SteVe DB...\n";
$txId = $db->table('transaction_start')->insertGetId([
    'event_timestamp' => Carbon::now()->subMinutes(5),
    'connector_pk' => $connectorPk,
    'id_tag' => $tag->tag_code,
    'start_timestamp' => Carbon::now()->subMinutes(5),
    'start_value' => 1000
]);
echo "SteVe TX ID: $txId\n";

// 4. Run Monitor (Sync phase)
echo "Running Monitor (Initial Sync)...\n";
Artisan::call('steve:monitor-transactions');
echo Artisan::output();

$session = ChargingSession::where('transaction_id', $txId)->first();
if ($session) {
    echo "Session created in CMS. ID: {$session->id} | Status: {$session->status} | Cost: \${$session->total_cost}\n";
} else {
    die("ERROR: Session not created in CMS.\n");
}

// 5. Add High Consumption Meter Value
echo "\nAdding high consumption (10kWh)...\n";
$db->table('connector_meter_value')->insert([
    'connector_pk' => $connectorPk,
    'transaction_pk' => $txId,
    'value_timestamp' => Carbon::now(),
    'value' => 11000, // 1000 + 10000 = 11000 Wh (10kWh consumed)
    'measurand' => 'Energy.Active.Import.Register',
    'unit' => 'Wh'
]);

// 6. Run Monitor (Enforcement phase)
echo "Running Monitor (Enforcement)...\n";
Artisan::call('steve:monitor-transactions');
echo Artisan::output();

// 7. Verify Results
$session->refresh();
echo "\n--- FINAL VERIFICATION ---\n";
echo "Session Status: {$session->status}\n";
echo "Stop Reason: {$session->stop_reason}\n";
echo "Final Cost: \${$session->total_cost}\n";

$audit = DB::table('remote_audit_logs')->where('charge_box_id', 'SimulatedCP001')->orderByDesc('created_at')->first();
if ($audit) {
    echo "Last Audit Log: [{$audit->action}] {$audit->details}\n";
}

if ($session->status === 'CreditStopped') {
    echo "SUCCESS: Credit enforcement triggered correctly!\n";
} else {
    echo "FAILURE: Session status is {$session->status}, expected CreditStopped.\n";
}

// Cleanup (Optional)
// $db->table('transaction_start')->where('transaction_pk', $txId)->delete();
// $db->table('connector_meter_value')->where('transaction_pk', $txId)->delete();
// $session->delete();

echo "\n--- TEST COMPLETE ---\n";
