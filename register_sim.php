<?php
use App\Models\Station;
use App\Models\Connector;
use App\Models\Location;
use Carbon\Carbon;

$loc = Location::firstOrCreate(
    ['name' => 'Laboratorio Virtual'],
    ['address' => 'Acceso Remoto', 'city' => 'CBBA', 'country' => 'BO']
);

$st = Station::updateOrCreate(
    ['charge_box_id' => 'SimulatedCP001'],
    [
        'name' => 'E2V Lab Simulator',
        'location_id' => $loc->id,
        'status' => 'Available',
        'last_heartbeat' => Carbon::now()
    ]
);

$st->connectors()->updateOrCreate(['connector_id' => 1], ['type' => 'CCS2', 'status' => 'AVAILABLE']);
$st->connectors()->updateOrCreate(['connector_id' => 2], ['type' => 'GBT', 'status' => 'AVAILABLE']);

echo "Station Registered: " . $st->id . " - " . $st->charge_box_id . "\n";
