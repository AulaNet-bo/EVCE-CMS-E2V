<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\WalletTransaction;

$email = 'jorge.ps.bo@gmail.com';
$u = User::where('email', $email)->first();

if (!$u) {
    die("User not found\n");
}

echo "Cleaning up pending recharges for user {$u->id}...\n";

$txs = WalletTransaction::where('user_id', $u->id)
    ->where('status', 'PENDING')
    ->where('type', 'RECHARGE')
    ->whereIn('amount', [92.08, 68.85])
    ->get();

foreach ($txs as $t) {
    echo "Deleting TX #{$t->id} | Amount: {$t->amount} | Desc: {$t->description}\n";
    $t->delete();
}

echo "Cleanup complete.\n";
