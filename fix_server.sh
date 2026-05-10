#!/bin/bash
# Total restoration of StationController.php (v4.03.4)

TARGET="/app/app/Http/Controllers/Api/V1/Mobile/StationController.php"

echo "--- Wiping and Rebuilding Controller ---"
cat > $TARGET << 'EOF'
<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StationController extends Controller
{
    public function index()
    {
        return response()->json(Station::with(['connectors', 'location'])->get());
    }

    public function show(Station $station)
    {
        return response()->json($station->load(['connectors', 'location']));
    }

    public function lookup(Request $request)
    {
        $request->validate(['charge_box_id' => 'required|string']);
        $cbId = trim($request->charge_box_id);
        
        Log::info("Station Lookup Request: [{$cbId}] sanitized.");

        $station = Station::with(['connectors', 'location'])
            ->where(DB::raw('LOWER(charge_box_id)'), strtolower($cbId))
            ->first();

        if (!$station) {
            Log::warning("Station Lookup Failed: [{$cbId}] not found.");
            return response()->json(['error' => 'Station not found'], 404);
        }

        Log::info("Station Lookup Success: Found ID [{$station->id}] for [{$cbId}]");
        return response()->json($station);
    }
}
EOF

chown www-data:www-data $TARGET
chmod 644 $TARGET

php artisan cache:clear
php artisan route:clear
php artisan optimize:clear

echo "--- RESTORATION COMPLETE ---"
