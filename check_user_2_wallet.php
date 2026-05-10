<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$wallet = DB::table('wallets')->where('user_id', 2)->first();
echo "USER_2_WALLET:\n";
print_r($wallet);
