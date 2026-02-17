<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::connection('steve')->getSchemaBuilder()->getColumnListing('transaction');
print_r($columns);

$first = DB::connection('steve')->table('transaction')->first();
print_r($first);
