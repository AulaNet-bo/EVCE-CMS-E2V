#!/bin/bash
# Full Restoration of Core System Files (v4.03.6)

write_file() {
    local TARGET=$1
    echo "Writing $TARGET..."
    cat > "$TARGET"
}

# 1. StationController.php
write_file "/app/app/Http/Controllers/Api/V1/Mobile/StationController.php" << 'EOF'
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

# 2. config/session.php
write_file "/app/config/session.php" << 'EOF'
<?php

use Illuminate\Support\Str;

return [
    'driver' => env('SESSION_DRIVER', 'database'),
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
    'encrypt' => env('SESSION_ENCRYPT', false),
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION'),
    'table' => env('SESSION_TABLE', 'sessions'),
    'store' => env('SESSION_STORE'),
    'lottery' => [2, 100],
    'cookie' => env('SESSION_COOKIE', Str::slug((string) env('APP_NAME', 'laravel')).'-session'),
    'path' => env('SESSION_PATH', '/'),
    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE_COOKIE'),
    'http_only' => env('SESSION_HTTP_ONLY', true),
    'same_site' => env('SESSION_SAME_SITE', 'lax'),
    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),
];
EOF

# 3. routes/api.php (Public Lookup)
write_file "/app/routes/api.php" << 'EOF'
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Mobile\AuthController;
use App\Http\Controllers\Api\V1\Mobile\StationController;
use App\Http\Controllers\Api\V1\Mobile\ChargingSessionController;
use App\Http\Controllers\Api\V1\Mobile\WalletController;
use App\Http\Controllers\Api\V1\Mobile\NotificationController;

Route::prefix('v1/mobile')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/stations/lookup', [StationController::class, 'lookup']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::get('/stations', [StationController::class, 'index']);
        Route::get('/stations/{station}', [StationController::class, 'show']);
        Route::post('/stations/{station}/start', [ChargingSessionController::class, 'start']);
        Route::post('/stations/{station}/stop', [ChargingSessionController::class, 'stop']);
        Route::get('/wallet', [WalletController::class, 'balance']);
    });
});
EOF

echo "--- Clearing Caches ---"
php artisan optimize:clear

echo "--- RESTORATION COMPLETE ---"
