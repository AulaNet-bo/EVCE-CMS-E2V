<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WalletTransaction;

foreach(WalletTransaction::all() as $tx) {
    if ($tx->wallet && !$tx->user_id) {
        $tx->user_id = $tx->wallet->user_id;
        $tx->save();
    }
    if ($tx->type === 'RECHARGE' && !$tx->payment_method) {
        $tx->payment_method = 'LIBELULA';
        $tx->save();
    }
}
echo "Backfill complete.\n";
