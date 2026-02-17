<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ChargingSession;

$count = ChargingSession::where('transaction_id', 16)->count();
echo "Tx 16 Exists? " . ($count > 0 ? "YES" : "NO") . "\n";
