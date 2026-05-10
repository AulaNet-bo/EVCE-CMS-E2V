<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$txs = DB::table('wallet_transactions')->where('user_id', 2)->orderBy('id', 'desc')->get();
echo "USER_2_TRANSACTIONS:\n";
print_r($txs->toArray());
