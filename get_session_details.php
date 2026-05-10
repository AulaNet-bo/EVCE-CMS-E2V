<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$session = \App\Models\ChargingSession::find(148507);
if ($session) {
    echo "CB_ID: " . $session->station->charge_box_id . "\n";
    echo "TX_ID: " . $session->transaction_id . "\n";
} else {
    echo "SESSION_NOT_FOUND\n";
}
