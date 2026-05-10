<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$duplicates = DB::table('wallets')
    ->select('user_id', DB::raw('count(*) as count'))
    ->groupBy('user_id')
    ->having('count', '>', 1)
    ->get();

print_r($duplicates);
