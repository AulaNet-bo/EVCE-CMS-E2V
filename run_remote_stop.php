<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$steve = app(\App\Services\SteveService::class);
$result = $steve->remoteStop('EPSCBLP56', 43);
echo "REMOTE_STOP_RESULT:\n";
print_r($result);
