<?php
use Illuminate\Support\Facades\DB;

try {
    echo "--- Steve Connector Schema ---\n";
    $columns = DB::connection('steve')->select("DESCRIBE connector");
    foreach ($columns as $col) {
        echo "{$col->Field} ({$col->Type})\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
