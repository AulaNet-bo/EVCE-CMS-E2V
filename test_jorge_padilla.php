<?php

use App\Models\User;
use App\Models\ChargingSession;
use App\Services\BillingService;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$userName = "Jorge Padilla";
$user = User::where('name', 'like', "%$userName%")->first();

if (!$user) {
    die("User $userName not found.\n");
}

echo "Found User: {$user->name} (ID: {$user->id})\n";

$session = ChargingSession::where('user_id', $user->id)->orderBy('id', 'desc')->first();

if (!$session) {
    die("No sessions found for this user.\n");
}

echo "Found Session ID: {$session->id} (Tx: {$session->transaction_id})\n";
echo "Total Cost: {$session->total_cost}\n";
echo "Energy Cost: {$session->energy_cost}\n";
echo "Session Fee: {$session->session_fee}\n";

$billing = app(BillingService::class);
echo "Triggering invoice simulation...\n";

// We use a reflection to call the private triggerInvoice method or just call finalizeBilling if it's not locked
// But easier to just call triggerInvoice via Reflection
$reflection = new \ReflectionClass(BillingService::class);
$method = $reflection->getMethod('triggerInvoice');
$method->setAccessible(true);
$method->invoke($billing, $session);

echo "Done. Check the logs for results.\n";
