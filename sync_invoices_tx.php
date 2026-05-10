<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ChargingSession;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Schema;

echo "Syncing invoice URLs from sessions to wallet transactions...\n";

$sessions = ChargingSession::whereNotNull('invoice_url')->get();
$refCol = Schema::hasColumn('wallet_transactions', 'reference_id') ? 'reference_id' : 'reference';

foreach ($sessions as $s) {
    $updated = WalletTransaction::where('user_id', $s->user_id)
        ->where('type', 'CHARGE')
        ->where($refCol, (string) $s->transaction_id)
        ->update(['invoice_url' => $s->invoice_url]);
    
    if ($updated) {
        echo "Updated TX for Session #{$s->id} (TX {$s->transaction_id})\n";
    }
}

echo "Done.\n";
