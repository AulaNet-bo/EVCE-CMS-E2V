<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\Station;
use App\Models\User;

$stations = Station::with(['location', 'connectors'])->where('is_active', true)->get();

echo "Total Active Stations: " . $stations->count() . "\n";
foreach ($stations as $s) {
    echo "Station: " . $s->name . " (ID: " . $s->id . ")\n";
    echo "  Location: " . ($s->location ? $s->location->name : "MISSING") . "\n";
    echo "  Connectors: " . $s->connectors->count() . "\n";
}

if ($stations->isEmpty()) {
    echo "ERROR: NO ACTIVE STATIONS FOUND IN DB!\n";
} else {
    echo "JSON MOCK:\n";
    echo $stations->toJson() . "\n";
}
