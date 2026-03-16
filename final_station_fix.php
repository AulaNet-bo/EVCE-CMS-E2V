<?php
use App\Models\Station;
use App\Models\Connector;
use App\Models\Location;
use App\Models\Tariff;
use Illuminate\Support\Facades\DB;

try {
    echo "--- Phase 1: Creating Connectors in Steve's Database ---\n";
    $steveIds = ['EPSCD12026', 'EPSCD22026', 'EPSCD32026'];

    foreach ($steveIds as $cbid) {
        // Check if connectors already exist in Steve
        $exists = DB::connection('steve')->table('connector')->where('charge_box_id', $cbid)->count();
        if ($exists == 0) {
            echo "Adding connectors for {$cbid} in Steve...\n";
            // Connector 1
            DB::connection('steve')->table('connector')->insert([
                'charge_box_id' => $cbid,
                'connector_id' => 1
            ]);
            // Connector 2
            DB::connection('steve')->table('connector')->insert([
                'charge_box_id' => $cbid,
                'connector_id' => 2
            ]);
        } else {
            echo "Connectors already exist for {$cbid} in Steve.\n";
        }
    }

    echo "--- Phase 2: Running Steve Status Sync ---\n";
    \Illuminate\Support\Facades\Artisan::call('steve:sync-status');
    echo \Illuminate\Support\Facades\Artisan::output();

    echo "--- Phase 3: Assigning Location and Tariff to actual Stations ---\n";
    $location = Location::where('name', 'Electropoint Las Palmas')->first();
    $tariff = Tariff::first(); // Standard tariff

    if ($location && $tariff) {
        foreach ($steveIds as $cbid) {
            $station = Station::where('charge_box_id', $cbid)->first();
            if ($station) {
                $station->location_id = $location->id;
                $station->tariff_id = $tariff->id;
                $station->name = "Las Palmas - " . $cbid; // Renaming to be clear
                $station->save();
                echo "Updated Station {$cbid} with Location ID {$location->id} and Tariff ID {$tariff->id}\n";
            } else {
                echo "Station {$cbid} not found in CMS even after sync!\n";
            }
        }
    } else {
        echo "Error: Location or Tariff not found.\n";
    }

    echo "--- Phase 4: Cleaning up fake stations ---\n";
    $fakeIds = ['EP-LP-D1', 'EP-LP-D2', 'EP-LP-D3'];
    foreach ($fakeIds as $fid) {
        $fake = Station::where('charge_box_id', $fid)->first();
        if ($fake) {
            // Delete associated connectors first to avoid orphaned records in CMS
            Connector::where('station_id', $fake->id)->delete();
            $fake->delete();
            echo "Deleted fake station {$fid} and its connectors.\n";
        }
    }

    echo "DONE.\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
