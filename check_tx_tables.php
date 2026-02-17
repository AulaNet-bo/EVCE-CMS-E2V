<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$c1 = DB::connection('steve')->getSchemaBuilder()->getColumnListing('transaction_start');
print_r($c1);
$c2 = DB::connection('steve')->getSchemaBuilder()->getColumnListing('transaction_stop');
print_r($c2);
