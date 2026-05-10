<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Station;
use App\Models\ChargingSession;
use App\Services\BillingService;
use App\Models\WalletTransaction;

$email = 'jorge.ps.bo@gmail.com';
$u = User::where('email', $email)->first();
$station = Station::where('charge_box_id', 'SimulatedCP001')->first();

if (!$u || !$station) {
    die("User or Station not found\n");
}

echo "Testing Final Flow for User #{$u->id} (Jorge Padilla S)...\n";

$txId = random_int(100000, 999999);
$session = ChargingSession::create([
    'user_id' => $u->id,
    'station_id' => $station->id,
    'connector_id' => 1,
    'transaction_id' => $txId,
    'start_time' => now(),
    'status' => 'Completed',
    'stop_time' => now()->addMinutes(10),
    'total_energy_kwh' => 2.0,
    'debited_amount' => 0,
]);

$billing = app(BillingService::class);
$billing->finalizeBilling($session);

$session->refresh();
echo "Session Cost: {$session->total_cost}\n";
echo "Invoice URL: " . ($session->invoice_url ?: 'None') . "\n";

$tx = WalletTransaction::where('user_id', $u->id)
    ->where('type', 'CHARGE')
    ->where('reference_id', (string) $txId)
    ->first();

if ($tx) {
    echo "Wallet Transaction ID: {$tx->id}\n";
    echo "TX Invoice URL: " . ($tx->invoice_url ?: 'None') . "\n";
} else {
    echo "ERROR: Wallet Transaction not found!\n";
}

$pendingCount = WalletTransaction::where('user_id', $u->id)
    ->where('status', 'PENDING')
    ->where('created_at', '>', now()->subMinutes(1))
    ->count();

echo "Pending Transactions Created: {$pendingCount} (Should be 0)\n";
