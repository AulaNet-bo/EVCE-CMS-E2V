<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::get('/payment-return-app', function (Illuminate\Http\Request $request) {
    $txId = $request->query('tx_id', '');
    return '<html><head><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="text-align:center; padding: 2rem; font-family: sans-serif;">
        <script>window.location.href="e2vapp://payment-complete?tx_id=' . $txId . '";</script>
        <h2 style="color: #0076D6;">Redireccionando a la App...</h2>
        <p>Procesando el pago, puedes cerrar esta ventana si la aplicación no se abre sola.</p>
    </body></html>';
});

Route::get('/disclaimer', [App\Http\Controllers\DisclaimerController::class, 'index'])->name('public.disclaimer');

// REMOTE CONTROL EMERGENCY PAGE
Route::get('/remote/login', [App\Http\Controllers\RemoteControlController::class, 'showLogin'])->name('remote.login');
Route::post('/remote/login', [App\Http\Controllers\RemoteControlController::class, 'login'])->name('remote.login.post');
Route::get('/remote/logout', [App\Http\Controllers\RemoteControlController::class, 'logout'])->name('remote.logout');

Route::get('/remote/control', [App\Http\Controllers\RemoteControlController::class, 'index'])->name('remote.index');
Route::post('/remote/start/{station}', [App\Http\Controllers\RemoteControlController::class, 'start'])->name('remote.start');
Route::post('/remote/stop/{station}', [App\Http\Controllers\RemoteControlController::class, 'stop'])->name('remote.stop');
