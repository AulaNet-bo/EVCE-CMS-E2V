<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WalletTransaction;

$txs = WalletTransaction::where('user_id', 2)->latest()->take(10)->get();

foreach ($txs as $t) {
    echo "ID: {$t->id} | Type: {$t->type} | Amount: {$t->amount} | Invoice: " . ($t->invoice_url ?: 'None') . " | Desc: {$t->description}\n";
}
