<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$cols = DB::connection('steve')->select('DESCRIBE transaction_start');
foreach ($cols as $col) {
    echo $col->Field . " - " . $col->Extra . "\n";
}
