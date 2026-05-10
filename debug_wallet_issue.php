<?php
putenv('DB_HOST=127.0.0.1');
$_ENV['DB_HOST'] = '127.0.0.1';

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\WalletTransaction;

$email = 'jorge.ps.bo@gmail.com';
$u = User::where('email', $email)->first();

if (!$u) {
    die("User not found: $email\n");
}

echo "User ID: " . $u->id . " | Name: " . $u->name . "\n";
echo "Wallet Balance: " . $u->wallet->balance . "\n";

echo "\n--- TRANSACTIONS ---\n";
$txs = WalletTransaction::where('user_id', $u->id)
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get();

foreach ($txs as $t) {
    echo "ID: {$t->id} | Type: {$t->type} | Amount: {$t->amount} | Status: {$t->status} | Desc: {$t->description} | Created: {$t->created_at}\n";
}
