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
     * Sync station data to Firebase (including connectors and status).
     */
    public static function syncStationData($chargeBoxId, array $data)
    {
        $dbUrl = rtrim(config('services.firebase.database_url', env('FIREBASE_DATABASE_URL')), '/');
        if (!$dbUrl || $dbUrl === '') return;

        try {
            // Update the specific station node
            $url = "{$dbUrl}/stations/{$chargeBoxId}.json";
            Http::patch($url, $data); // Use PATCH to avoid overwriting unrelated fields
        } catch (\Throwable $e) {
            Log::error("Firebase sync error for station {$chargeBoxId}: " . $e->getMessage());
        }
    }
}
