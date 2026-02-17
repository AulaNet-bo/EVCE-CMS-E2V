<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ChargingSession;

$session = ChargingSession::where('transaction_id', 16)->first();
if ($session) {
    $session->delete();
    echo "Deleted Session 16 from CMS.\n";
} else {
    echo "Session 16 not found.\n";
}
