<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$session = DB::table('charging_sessions')->where('transaction_id', 45)->first();
echo "SESSION_TX_45:\n";
print_r($session);
