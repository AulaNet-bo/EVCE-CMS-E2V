echo "--- DIAGNOSTIC START ---\n";
echo "ACTIVE STATIONS: " . \App\Models\Station::where('is_active', true)->count() . "\n";
echo "TOTAL LOCATIONS: " . \App\Models\Location::count() . "\n";
foreach (\App\Models\Station::with(['location', 'connectors'])->where('is_active', true)->get() as $s) {
    echo "ID: " . $s->id . " | NAME: " . $s->name . "\n";
    if ($s->location) {
        echo "  - LOCATION: " . $s->location->name . " (ID: " . $s->location->id . ")\n";
        echo "  - COORDS: Lat: " . $s->location->latitude . " | Lon: " . $s->location->longitude . "\n";
    } else {
        echo "  - WARNING: NO LOCATION ASSIGNED!\n";
    }
    echo "  - CONNECTORS: " . $s->connectors->count() . "\n";
}
echo "--- DIAGNOSTIC END ---\n";
