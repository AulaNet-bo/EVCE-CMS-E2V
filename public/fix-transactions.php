<?php

use Illuminate\Support\Facades\DB;
use App\Models\WalletTransaction;

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting data fix...\n";

// 1. Fix user_id
$affected = DB::statement("
    UPDATE wallet_transactions 
    JOIN wallets ON wallet_transactions.wallet_id = wallets.id 
    SET wallet_transactions.user_id = wallets.user_id 
    WHERE wallet_transactions.user_id IS NULL
");
echo "Updated user_id for affected transactions.\n";

// 2. Fix payment_method
$affected2 = DB::table('wallet_transactions')
    ->where('payment_method', 'LIBELULA')
    ->where('created_at', '>', '2026-03-30 00:00:00')
    ->update(['payment_method' => 'ATC_QR']);
echo "Updated payment_method to ATC_QR for $affected2 transactions.\n";

echo "Done.\n";
