<?php

use App\Models\Company;
use App\Models\Tariff;
use App\Models\Location;
use App\Models\Station;
use App\Models\Connector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "--- Debug Seeder v3 ---\n";

try {
    echo "Checking schema for 'google_maps_url'...\n";
    if (Schema::hasColumn('locations', 'google_maps_url')) {
        echo "SUCCESS: 'google_maps_url' column exists.\n";
    } else {
        echo "FAILURE: 'google_maps_url' column DOES NOT exist.\n";
        // Let's print all columns
        print_r(Schema::getColumnListing('locations'));
    }

    $company = Company::firstOrCreate([
        'name' => 'Electropoint',
    ], [
        'email' => 'admin@electropoint.com',
        'is_active' => true,
    ]);
    echo "Company ID: {$company->id}\n";

    $tariff = Tariff::firstOrCreate([
        'name' => 'Tarifa Estándar',
    ], [
        'currency' => 'BOB',
        'cost_price_kwh' => 1.5,
        'valid_from' => now()->subDay()->toDateTimeString(),
        'b1_start' => '00:00:00',
        'b1_end' => '23:59:59',
        'b1_price_kwh' => 2.5,
    ]);
    echo "Tariff ID: {$tariff->id}\n";

    echo "Attempting to create Location...\n";
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
    echo "Location created successfully! ID: {$location->id}\n";

    echo "Creating stations...\n";
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
        echo "  - Station {$i} (ID: {$station->id})\n";
    }

    echo "DONE.\n";

} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . ":" . $e->getLine() . "\n";
    // If it's a QueryException, show the SQL
    if ($e instanceof \Illuminate\Database\QueryException) {
        echo "SQL: " . $e->getSql() . "\n";
        echo "BINDINGS: " . json_encode($e->getBindings()) . "\n";
    }
}
