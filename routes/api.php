<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Mobile\AuthController;
use App\Http\Controllers\Api\V1\Mobile\StationController;
use App\Http\Controllers\Api\V1\Mobile\ChargingSessionController;
use App\Http\Controllers\Api\V1\Mobile\WalletController;
use App\Http\Controllers\Api\V1\Mobile\NotificationController;

use App\Http\Controllers\Api\V1\SAP\SAPController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- WEBHOOKS ---
Route::any('/webhooks/libelula', [App\Http\Controllers\Api\WebhookController::class, 'libelula'])->name('api.webhooks.libelula');

// --- MOBILE APP API (V1) ---
Route::prefix('v1/mobile')->group(function () {
    // Public (Auth)
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/google-login', [AuthController::class, 'googleLogin']);
    Route::get('/config', [App\Http\Controllers\Api\V1\Mobile\SystemController::class, 'config']);
    Route::post('/config/seen', [App\Http\Controllers\Api\V1\Mobile\SystemController::class, 'trackSeen']);

    // Protected
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/profile', [AuthController::class, 'update']);
        Route::post('/fcm-token', [AuthController::class, 'updateFcmToken']);

        // Stations
        Route::get('/stations', [StationController::class, 'index']);
        Route::get('/locations', [\App\Http\Controllers\Api\V1\Mobile\LocationController::class, 'index']);
        Route::get('/stations/lookup', [StationController::class, 'lookup']);
        Route::get('/stations/{station}', [StationController::class, 'show']);
        Route::post('/stations/{station}/start', [ChargingSessionController::class, 'start']);
        Route::post('/stations/{station}/stop', [ChargingSessionController::class, 'stop']);

        // Wallet
        Route::get('/wallet', [WalletController::class, 'balance']);
        Route::get('/wallet/transactions', [WalletController::class, 'history']);
        Route::post('/wallet/topup', [WalletController::class, 'topup']);
        Route::post('/wallet/libelula/checkout', [WalletController::class, 'libelulaCheckout']);
        Route::get('/wallet/libelula/status/{transactionId}', [WalletController::class, 'libelulaStatus']);
        Route::delete('/wallet/libelula/pending/{transactionId}', [WalletController::class, 'deletePending']);

        // Sessions
        Route::get('/sessions', [ChargingSessionController::class, 'index']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    });
});

// --- SAP INTEGRATION API (V1) ---
// Secured with dedicated API Tokens or Auth middleware
Route::prefix('v1/sap')->middleware('auth:sanctum')->group(function () {
    Route::get('/export', [SAPController::class, 'exportData']);
    Route::post('/sync', [SAPController::class, 'markSynced']);
});
