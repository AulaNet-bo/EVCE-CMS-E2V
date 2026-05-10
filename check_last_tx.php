<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tx = DB::table('wallet_transactions')->orderBy('id', 'desc')->limit(5)->get();
echo "LAST_TRANSACTIONS:\n";
print_r($tx->toArray());
