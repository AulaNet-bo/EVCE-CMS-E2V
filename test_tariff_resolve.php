<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $station = \App\Models\Station::first();
    $tariff = \App\Models\Tariff::resolveForStation($station);
    echo "TARIFF_FOUND: " . ($tariff ? $tariff->name : 'NONE') . "\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
