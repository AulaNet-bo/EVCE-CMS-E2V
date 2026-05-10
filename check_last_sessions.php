<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sessions = DB::table('charging_sessions')->orderBy('id', 'desc')->limit(5)->get();
echo "LAST_SESSIONS:\n";
print_r($sessions->toArray());
