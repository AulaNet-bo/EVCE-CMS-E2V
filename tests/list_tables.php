<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Listing Tables in Steve DB...\n";

$tables = DB::connection('steve')->select('SHOW TABLES');

foreach ($tables as $table) {
    echo current((array) $table) . "\n";
}
