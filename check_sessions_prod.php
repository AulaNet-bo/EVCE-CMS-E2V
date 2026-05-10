<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ChargingSession;

$txIds = [38, 39, 40, 41];

foreach ($txIds as $id) {
    $s = ChargingSession::where('transaction_id', (string)$id)->first();
    if (!$s) {
        echo "Tx: $id | NOT FOUND\n";
        continue;
    }
    echo "Tx: $id | Status: {$s->status} | User: " . ($s->user->name ?? 'N/A') . " | Debited: {$s->debited_amount} | Cost: {$s->total_cost} | Created: {$s->created_at}\n";
}
