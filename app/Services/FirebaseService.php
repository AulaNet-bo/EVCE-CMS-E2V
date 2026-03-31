<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    /**
     * Sync user balance to Firebase Realtime Database.
     */
    public static function syncUserBalance($userId, $balance)
    {
        $dbUrl = rtrim(config('services.firebase.database_url', env('FIREBASE_DATABASE_URL')), '/');
        if (!$dbUrl || $dbUrl === '') {
            return;
        }

        try {
            $url = "{$dbUrl}/users/{$userId}/wallet/balance.json";
            Http::put($url, (float) $balance);
        } catch (\Throwable $e) {
            Log::error("Firebase sync error for user {$userId}: " . $e->getMessage());
        }
    }

    /**
     * Sync station status to Firebase.
     */
    public static function syncStationStatus($stationId, $status)
    {
        $dbUrl = rtrim(config('services.firebase.database_url', env('FIREBASE_DATABASE_URL')), '/');
        if (!$dbUrl || $dbUrl === '') return;

        try {
            $url = "{$dbUrl}/stations/{$stationId}/status.json";
            Http::put($url, $status);
        } catch (\Throwable $e) {
            Log::error("Firebase sync error for station {$stationId}: " . $e->getMessage());
        }
    }
}
