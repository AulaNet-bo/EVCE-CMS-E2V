<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = DB::table('users')->where('created_at', '>=', '2026-05-05')->get();
echo "USERS_CREATED_TODAY:\n";
print_r($users->toArray());
