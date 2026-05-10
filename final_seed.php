<?php
use App\Models\Station;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

// Clean up old sims
Station::where('charge_box_id', 'SimulatedCP001')->delete();
Station::where('charge_box_id', 'SIMULADOR01')->delete();

$loc = Location::firstOrCreate(
    ['name' => 'Laboratorio Virtual'],
    ['address' => 'Acceso Remoto', 'city' => 'CBBA', 'country' => 'BO']
);

$st = Station::create([
    'charge_box_id' => 'SIMULADOR01',
    'name' => 'SIMULADOR E2V LAB',
    'location_id' => $loc->id,
    'status' => 'Available',
    'is_active' => true
]);

$st->connectors()->create(['connector_id' => 1, 'type' => 'CCS2', 'status' => 'AVAILABLE']);
$st->connectors()->create(['connector_id' => 2, 'type' => 'GBT', 'status' => 'AVAILABLE']);

echo "SUCCESS: Registered SIMULADOR01 with ID: " . $st->id . "\n";
foreach ($st->connectors as $c) {
    echo " - Connector " . $c->connector_id . ": " . $c->type . "\n";
}
