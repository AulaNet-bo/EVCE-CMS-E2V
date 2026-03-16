<?php

use App\Models\Company;
use App\Models\Tariff;
use App\Models\Location;
use App\Models\Station;
use App\Models\Connector;
use Illuminate\Support\Str;

$company = Company::first();
if (!$company) {
    $company = Company::create([
        'name' => 'Electropoint',
        'email' => 'admin@electropoint.com',
        'is_active' => true,
    ]);
    echo "Created default Company\n";
}

$tariff = Tariff::first();
if (!$tariff) {
    $tariff = Tariff::create([
        'name' => 'Tarifa Estándar',
        'currency' => 'BOB',
        'cost_price_kwh' => 1.5,
        'price_session' => 0,
        'valid_from' => now()->subDay(),
        'b1_start' => 0,
        'b1_end' => 9999,
        'b1_price_kwh' => 2.5,
        'b1_cost_kwh' => 1.5,
        'b1_price_min' => 0,
    ]);
    echo "Created default Tariff\n";
}

$location = Location::create([
    'company_id' => $company->id,
    'name' => 'Electropoint Las Palmas',
    'address' => 'Las Palmas, Santa Cruz de la Sierra',
    'latitude' => -17.809446,
    'longitude' => -63.207641,
    'google_maps_url' => 'https://www.google.com/maps?q=-17.809446,-63.207641',
    'city' => 'Santa Cruz',
    'country' => 'Bolivia',
    'is_public' => true,
]);

echo "Created Location: ID {$location->id}\n";

for ($i = 1; $i <= 3; $i++) {
    $chargeBoxId = "EP-LP-D{$i}";
    $station = Station::create([
        'charge_box_id' => $chargeBoxId,
        'location_id' => $location->id,
        'tariff_id' => $tariff->id,
        'name' => "Dispensador {$i}",
        'model' => 'Terra DC',
        'vendor' => 'ABB',
        'is_active' => true,
    ]);

    echo "Created Station {$i}: ID {$station->id} (Box: {$chargeBoxId})\n";

    // GBT Connector
    Connector::create([
        'station_id' => $station->id,
        'connector_id' => 1,
        'type' => 'GBT',
        'max_power_kw' => 60.0,
        'status' => 'Available',
        'connector_pk' => $chargeBoxId . '-1',
    ]);

    // CCS2 Connector
    Connector::create([
        'station_id' => $station->id,
        'connector_id' => 2,
        'type' => 'CCS2',
        'max_power_kw' => 60.0,
        'status' => 'Available',
        'connector_pk' => $chargeBoxId . '-2',
    ]);

    echo "  Added GBT and CCS2 connectors to Station {$i}\n";
}

echo "Seeding completed successfully!\n";
