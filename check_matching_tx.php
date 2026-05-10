<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tx = DB::table('wallet_transactions')->where('reference_id', 'LIKE', '%BULK-CP32ASE2%')->first();
echo "MATCHING_TX:\n";
print_r($tx);
