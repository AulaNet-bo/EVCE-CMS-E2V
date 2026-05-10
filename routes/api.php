<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Mobile\AuthController;
use App\Http\Controllers\Api\V1\Mobile\StationController;
use App\Http\Controllers\Api\V1\Mobile\ChargingSessionController;
use App\Http\Controllers\Api\V1\Mobile\WalletController;
use App\Http\Controllers\Api\V1\Mobile\NotificationController;
use App\Http\Controllers\Api\V1\Mobile\LocationController;
use App\Http\Controllers\Api\V1\Sap\SapExportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| MOBILE APP API (V1) - MODULAR STRUCTURE
|--------------------------------------------------------------------------
*/
Route::prefix('v1/mobile')->group(function () {
    // 0. AUTH & SYSTEM (Public)
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/google-login', [AuthController::class, 'googleLogin']);
    Route::get('/config', [App\Http\Controllers\Api\V1\Mobile\SystemController::class, 'config']);
    Route::post('/config/seen', [App\Http\Controllers\Api\V1\Mobile\SystemController::class, 'trackSeen']);

    // Public Webhooks
    Route::post('/webhooks/libelula', [App\Http\Controllers\Api\WebhookController::class, 'libelula'])->name('api.webhooks.libelula');

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        
        // 1. MAP MODULE (Stations & Locations)
        Route::prefix('map')->group(function () {
            Route::get('/locations', [LocationController::class, 'index']);
            Route::get('/stations', [StationController::class, 'index']);
            Route::get('/stations/lookup', [StationController::class, 'lookup']);
            Route::get('/stations/{station}', [StationController::class, 'show']);
        });

        // 2. WALLET MODULE (Balance & Payments)
        Route::prefix('wallet')->group(function () {
            Route::get('/', [WalletController::class, 'balance']);
            Route::get('/transactions', [WalletController::class, 'history']);
            Route::post('/topup', [WalletController::class, 'topup']);
            Route::post('/libelula/checkout', [WalletController::class, 'libelulaCheckout']);
            Route::get('/libelula/status/{transactionId}', [WalletController::class, 'libelulaStatus']);
            Route::delete('/libelula/pending/{transactionId}', [WalletController::class, 'deletePending']);
            Route::post('/tags/{tag}', [WalletController::class, 'updateTag']);
        });

        // 3. SESSIONS MODULE (Charging Control & History)
        Route::prefix('sessions')->group(function () {
            Route::get('/', [ChargingSessionController::class, 'index']);
            Route::post('/{station}/start', [ChargingSessionController::class, 'start']);
            Route::post('/{station}/stop', [ChargingSessionController::class, 'stop']);
            Route::post('/{session}/cancel', [ChargingSessionController::class, 'cancel']);
        });

        // 4. PROFILE MODULE (User & Notifications)
        Route::prefix('profile')->group(function () {
            Route::get('/', [AuthController::class, 'profile']);
            Route::post('/', [AuthController::class, 'update']);
            Route::post('/fcm-token', [AuthController::class, 'updateFcmToken']);
            Route::get('/notifications', [NotificationController::class, 'index']);
            Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        });

        // LEGACY REDIRECTS (To avoid breaking the current App immediately if needed)
        // Note: Better to update the App, but keep these for compatibility during transition.
        Route::get('/locations', [LocationController::class, 'index']);
        Route::get('/stations', [StationController::class, 'index']);
        Route::get('/wallet', [WalletController::class, 'balance']);
        Route::get('/sessions', [ChargingSessionController::class, 'index']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/stations/{station}/start', [ChargingSessionController::class, 'start']);
        Route::post('/stations/{station}/stop', [ChargingSessionController::class, 'stop']);
    });
});

// --- SAP INTEGRATION API (V1) ---
Route::prefix('v1/sap')->middleware('auth:sanctum')->group(function () {
    Route::get('/export', [SapExportController::class, 'exportData']);
    Route::post('/sync', [SapExportController::class, 'markSynced']);
});
