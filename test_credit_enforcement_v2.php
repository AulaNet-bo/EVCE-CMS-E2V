<?php
putenv('DB_HOST=127.0.0.1');
$_ENV['DB_HOST'] = '127.0.0.1';

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ChargingSession;
use App\Models\Station;
use App\Models\Tariff;
use App\Services\BillingService;
use App\Services\SteveService;
use Illuminate\Support\Facades\DB;

echo "--- REALISTIC CREDIT ENFORCEMENT TEST ---\n";

// 1. Prepare User and Wallet
$u = User::find(1);
$u->wallet->update(['balance' => 2.00]);
echo "Initial User Balance: \${$u->balance}\n";

// 2. Prepare Station and Session
$station = Station::first();
$tariff = Tariff::find(1);
$txId = "SIM_" . time();

$session = ChargingSession::create([
    'user_id' => $u->id,
    'station_id' => $station->id,
    'connector_id' => 1,
    'transaction_id' => $txId,
    'status' => 'Active',
    'tariff_id' => $tariff->id,
    'start_time' => now()->subMinutes(10),
    'meter_start' => 1000,
    'total_energy_kwh' => 0,
    'debited_amount' => 0,
    'currency' => 'USD'
]);

echo "Created Active Session: #{$session->id} | Tx: $txId\n";

$billing = app(BillingService::class);
$steve = app(SteveService::class);

// 3. Test Case 1: Small Consumption (Might trigger stop if balance < $2.45)
echo "\n[Test Case 1] Consuming 1kWh (Cost: \${$tariff->price_session} + \$" . (1 * ($tariff->b1_price_kwh + $tariff->b4_price_kwh)) . " = \$2.45)...\n";
$pricing = $billing->calculateSessionCost($session, 1.0);
echo "Calculated Cost: \${$pricing['total']} (Energy: \${$pricing['energy_cost']}, Session: \${$pricing['session_fee']}, Time: \${$pricing['time_fee']})\n";

$ok = $billing->processIncrementalDebit($session, $pricing);
$u->wallet->refresh();
$session->refresh();

if ($ok) {
    echo "SUCCESS: Incremental debit processed. New Balance: \${$u->wallet->balance} | Debited: \${$session->debited_amount}\n";
} else {
    echo "STOP TRIGGERED: processIncrementalDebit returned false. Balance (\${$u->wallet->balance}) < Delta (\${$pricing['total']} - {$session->debited_amount})\n";
    
    echo "Sending RemoteStop to SteVe...\n";
    try {
        $steve->remoteStop($station->charge_box_id, (int)$txId, $u->id);
    } catch (\Exception $e) {
        echo "SteVe RemoteStop Attempted (failed as expected due to missing audit table or API)\n";
    }
    $session->update(['status' => 'CreditStopped', 'stop_reason' => 'CreditLimitExceeded']);
}

// 4. Test Case 2: High Consumption (Out of Credit)
echo "\n[Test Case 2] Consuming 10kWh (Total Cost: \$4.50)...\n";
$session->update(['total_energy_kwh' => 10.0]); // Simulate meter update
$pricing = $billing->calculateSessionCost($session, 10.0);
echo "Calculated Cost: \${$pricing['total']} (Energy: \${$pricing['energy_cost']}, Session: \${$pricing['session_fee']})\n";

$ok = $billing->processIncrementalDebit($session, $pricing);

if (!$ok) {
    echo "STOP TRIGGERED: processIncrementalDebit returned false (Insufficient Funds).\n";
    
    // Simulate the Monitor command action
    echo "Sending RemoteStop to SteVe...\n";
    try {
        $steve->remoteStop($station->charge_box_id, (int)$txId, $u->id);
    } catch (\Exception $e) {
        echo "SteVe RemoteStop Attempted (failed due to missing audit table or API: " . $e->getMessage() . ")\n";
    }
    $session->update(['status' => 'CreditStopped', 'stop_reason' => 'CreditLimitExceeded']);
    
    echo "Session Status Updated: {$session->status}\n";
} else {
    echo "FAILURE: Debit passed but should have triggered STOP.\n";
}

// 5. Final Audit Log Check
echo "\n--- AUDIT LOG CHECK ---\n";
try {
    $audit = DB::table('remote_audit_logs')->where('details', 'like', "%Tx: $txId%")->first();
    if ($audit) {
        echo "Found Audit Log: [{$audit->action}] {$audit->details}\n";
    } else {
        echo "No audit log found for this transaction stop.\n";
    }
} catch (\Exception $e) {
    echo "Could not check audit log: " . $e->getMessage() . "\n";
}

// Cleanup
$session->delete();
// Restore balance for future tests
$u->wallet->update(['balance' => 0.00]);

echo "\n--- TEST COMPLETE ---\n";
