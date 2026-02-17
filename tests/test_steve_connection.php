<?php

use App\Models\Steve\Station;
use App\Models\Steve\ChargingSession;
use Illuminate\Support\Facades\DB;

echo "Testing connection to Steve DB...\n";

try {
    $stationCount = Station::count();
    echo "Station count: $stationCount\n";

    $sessionCount = ChargingSession::count();
    echo "Session count: $sessionCount\n";

    echo "Connection Successful!\n";
} catch (\Exception $e) {
    echo "Connection Failed: " . $e->getMessage() . "\n";
}
