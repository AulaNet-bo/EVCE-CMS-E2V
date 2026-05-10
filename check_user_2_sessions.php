<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sessions = DB::table('charging_sessions')->where('user_id', 2)->where('status', 'Completed')->orderBy('id', 'desc')->limit(5)->get();
echo "USER_2_COMPLETED_SESSIONS:\n";
print_r($sessions->toArray());
