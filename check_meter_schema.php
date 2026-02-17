<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::connection('steve')->getSchemaBuilder()->getColumnListing('connector_meter_value');
print_r($columns);

$first = DB::connection('steve')->table('connector_meter_value')->first();
print_r($first);
