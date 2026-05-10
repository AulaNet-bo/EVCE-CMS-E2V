<?php
putenv('DB_HOST=127.0.0.1');
$_ENV['DB_HOST'] = '127.0.0.1';

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tariff;
use App\Models\ChargingSession;
use App\Services\BillingService;
use Carbon\Carbon;

$tariff = Tariff::find(1);
echo "Tariff B1: {$tariff->b1_start} - {$tariff->b1_end} (@\${$tariff->b1_price_kwh})\n";
echo "Tariff B4: {$tariff->b4_start} - {$tariff->b4_end} (@\${$tariff->b4_price_kwh})\n";

$session = new ChargingSession([
    'start_time' => Carbon::now()->subMinutes(10),
    'tariff_id' => $tariff->id
]);
$session->setRelation('tariff', $tariff);

$start = $session->start_time;
$stop = Carbon::now();
echo "Start: " . $start->toDateTimeString() . " (" . $start->timezoneName . ")\n";
echo "Stop:  " . $stop->toDateTimeString() . " (" . $stop->timezoneName . ")\n";
echo "Diff:  " . $stop->diffInSeconds($start) . " seconds\n";

$billing = new BillingService();
$pricing = $billing->calculateSessionCost($session, 1.0);

echo "Energy: 1.0 kWh\n";
echo "Total Cost: \${$pricing['total']}\n";
foreach ($pricing['breakdown'] as $b) {
    echo " - Block {$b['block']}: {$b['energy_kwh']} kWh (Overlap: {$b['seconds']}s)\n";
}
