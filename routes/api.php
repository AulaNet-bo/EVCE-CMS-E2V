<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Mobile\AuthController;
use App\Http\Controllers\Api\V1\Mobile\StationController;
use App\Http\Controllers\Api\V1\Mobile\ChargingSessionController;
use App\Http\Controllers\Api\V1\Mobile\WalletController;

use App\Http\Controllers\Api\V1\Sap\SapIntegrationController;

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
    
    // Protected
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        
        // Stations
        Route::get('/stations', [StationController::class, 'index']);
        Route::get('/stations/{station}', [StationController::class, 'show']);
        Route::post('/stations/{station}/start', [ChargingSessionController::class, 'start']);
        Route::post('/stations/{station}/stop', [ChargingSessionController::class, 'stop']);
        
        // Wallet
        Route::get('/wallet', [WalletController::class, 'balance']);
        Route::get('/wallet/transactions', [WalletController::class, 'history']);
        Route::post('/wallet/topup', [WalletController::class, 'topup']);
        Route::post('/wallet/libelula/checkout', [WalletController::class, 'libelulaCheckout']);
        
        // Sessions
        Route::get('/sessions', [ChargingSessionController::class, 'index']);
    });
});

// --- SAP INTEGRATION API (V1) ---
// Secured with dedicated API Tokens or IP Whitelist
Route::prefix('v1/sap')->middleware('auth:sanctum')->group(function () {
    
    // Sync Customers
    Route::get('/customers', [SapIntegrationController::class, 'getCustomers']);
    Route::post('/customers', [SapIntegrationController::class, 'upsertCustomer']);
    
    // Financials
    Route::get('/transactions', [SapIntegrationController::class, 'getTransactions']);
    Route::get('/invoices', [SapIntegrationController::class, 'getInvoices']);
});
