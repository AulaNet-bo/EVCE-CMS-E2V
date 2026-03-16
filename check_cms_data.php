<?php
use App\Models\Station;
use App\Models\Location;

try {
    echo "--- CMS Stations Check ---\n";
    $stations = Station::with('location')->get();
    echo "Found " . count($stations) . " Stations in CMS:\n";
    foreach ($stations as $s) {
        $locName = $s->location ? $s->location->name : 'NONE';
        $tariffName = $s->tariff ? $s->tariff->name : 'NONE';
        echo "  - ID: {$s->charge_box_id} | Name: {$s->name} | Location: {$locName} | Tariff: {$tariffName} (ID: " . ($s->tariff_id ?? 'NULL') . ")\n";
    }

    echo "\n--- Existing Locations ---\n";
    $locations = Location::all();
    foreach ($locations as $l) {
        echo "  - [ID: {$l->id}] {$l->name}\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
