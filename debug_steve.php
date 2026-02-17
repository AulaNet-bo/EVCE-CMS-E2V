<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// List tables in Steve DB
$tables = DB::connection('steve')->select('SHOW TABLES');
echo "--- Tables in Steve DB ---\n";
foreach ($tables as $t) {
    $val = array_values((array)$t)[0];
    echo $val . "\n";
}

echo "\n--- Transaction 1 ---\n";
$tx = DB::connection('steve')->table('transaction')->where('transactionPk', 1)->first(); // Steve often uses transactionPk
if (!$tx) {
    $tx = DB::connection('steve')->table('transaction')->where('id', 1)->first();
}
print_r($tx);

echo "\n--- Meter Values ---\n";
// Try to guess the table name based on common Steve versions
$mvTable = 'connector_meter_value';
if (!Schema::connection('steve')->hasTable($mvTable)) {
    $mvTable = 'transaction_meter_value'; // Older Steve
}
if (Schema::connection('steve')->hasTable($mvTable)) {
    $mvs = DB::connection('steve')->table($mvTable)->orderBy('pd_timestamp', 'desc')->limit(5)->get(); // pd_timestamp is common
    print_r($mvs);
} else {
    echo "Could not find a known meter value table.\n";
}
