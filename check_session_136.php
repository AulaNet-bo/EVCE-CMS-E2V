<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$session = DB::table('charging_sessions')->where('id', 136)->first();
echo "SESSION_136:\n";
print_r($session);
