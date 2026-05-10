<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::table('wallets')->where('user_id', 2)->update([
    'balance' => 159.00,
    'updated_at' => now()
]);
echo "BALANCE_FIXED_FOR_USER_2\n";
