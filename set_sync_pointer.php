<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$max = DB::connection('steve')->table('transaction')->max('transaction_pk');
echo "MAX_PK: " . $max . "\n";
if ($max) {
    App\Models\ChargingSession::create([
        'transaction_id' => (string)$max,
        'status' => 'Completed',
        'station_id' => 1,
        'connector_id' => 1,
        'start_time' => now(),
        'stop_time' => now(),
        'total_cost' => 0,
        'total_energy_kwh' => 0
    ]);
    echo "Inserted dummy session with ID $max to prevent re-syncing history.\n";
}
