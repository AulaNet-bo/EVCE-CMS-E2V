<?php
use App\Models\Station;
use Illuminate\Support\Facades\DB;

echo "--- STATIONS TABLE ---\n";
$stations = DB::table('stations')->get();
foreach ($stations as $s) {
    echo "ID: " . $s->id . " | charge_box_id: " . ($s->charge_box_id ?? 'N/A') . " | name: " . ($s->name ?? 'N/A') . "\n";
}

$sim = Station::where('charge_box_id', 'SimulatedCP001')->first();
if ($sim) {
    echo "SimulatedCP001 FOUND with ID: " . $sim->id . "\n";
} else {
    echo "SimulatedCP001 NOT FOUND\n";
}
