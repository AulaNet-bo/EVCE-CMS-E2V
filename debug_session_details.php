<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ChargingSession;

$s = ChargingSession::find(148497);
if ($s) {
    echo "Session 148497 Details:\n";
    echo "Station ID: {$s->station_id}\n";
    echo "RFID Tag ID: " . ($s->rfid_tag_id ?: 'NULL') . "\n";
    echo "User ID: " . ($s->user_id ?: 'NULL') . "\n";
    echo "Status: {$s->status}\n";
} else {
    echo "Session 148497 NOT FOUND\n";
}
