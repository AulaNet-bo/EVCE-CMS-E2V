<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Station;
use App\Models\ChargingSession;
use App\Services\BillingService;
use Carbon\Carbon;

$email = 'jorge.ps.bo@gmail.com';
$u = User::where('email', $email)->first();
$station = Station::where('charge_box_id', 'SimulatedCP001')->first();

if (!$u || !$station) {
    die("User or Station not found\n");
}

$u->wallet->increment('balance', 100);
echo "Added 100 BOB to wallet. Current balance: {$u->wallet->balance}\n";

echo "Starting simulation for User {$u->id} at Station {$station->charge_box_id}...\n";

// 1. Create session
$session = ChargingSession::create([
    'user_id' => $u->id,
    'station_id' => $station->id,
    'connector_id' => 1,
    'transaction_id' => random_int(100000, 999999),
    'start_time' => now(),
    'status' => 'Charging',
    'total_energy_kwh' => 0,
    'debited_amount' => 0,
]);

echo "Session #{$session->id} started (TX #{$session->transaction_id}).\n";

// 2. Process initial fee (if any)
$billing = app(BillingService::class);
$billing->processInitialFee($session);

// 3. Simulate consumption
$session->total_energy_kwh = 15.5; // 15.5 kWh
$session->save();

// 4. Finalize billing (this should debit and trigger invoice)
$session->stop_time = now()->addMinutes(30);
$session->status = 'Completed';
$session->save();

echo "Finalizing billing...\n";
$billing->finalizeBilling($session);

$session->refresh();
echo "FINAL RESULTS:\n";
echo "Total Cost: {$session->total_cost}\n";
echo "Debited Amount: {$session->debited_amount}\n";
echo "Invoice URL: " . ($session->invoice_url ?: 'None') . "\n";

// 5. Check for PENDING transactions
$pending = \App\Models\WalletTransaction::where('user_id', $u->id)
    ->where('status', 'PENDING')
    ->where('created_at', '>', now()->subMinutes(1))
    ->count();

echo "New Pending Transactions: {$pending} (Should be 0)\n";
