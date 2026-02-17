<?php

use App\Models\Steve\Station;
use App\Models\Steve\Connector;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking Connectors...\n";

$stations = Station::all();

foreach ($stations as $station) {
    echo "Station: " . $station->charge_box_id . "\n";
    $connectors = $station->connectors;
    echo "  Total Connectors Found: " . $connectors->count() . "\n";

    foreach ($connectors as $c) {
        echo "    - Attributes: " . implode(', ', array_keys($c->getAttributes())) . "\n";
        echo "    - PK: " . $c->connector_pk . " | ID: " . ($c->connectorId ?? 'NULL') . "\n";
    }
    echo "--------------------------------\n";
}
