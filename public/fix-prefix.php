<?php

use Illuminate\Support\Facades\DB;

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Updating existing methods to prefix with LIBELULA/...\n";

$affected = DB::table('wallet_transactions')
    ->where('payment_method', 'ATC_QR')
    ->orWhere('payment_method', 'ATC_TARJETA')
    ->orWhere('payment_method', 'QR')
    ->update([
        'payment_method' => DB::raw("CONCAT('LIBELULA/', payment_method)")
    ]);

echo "Updated $affected records.\n";
echo "Done.\n";
