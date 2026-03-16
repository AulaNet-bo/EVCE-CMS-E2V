<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    echo "--- Database Inspection ---\n";
    $tables = ['locations', 'companies', 'stations', 'connectors'];

    foreach ($tables as $table) {
        $exists = Schema::hasTable($table) ? "YES" : "NO";
        echo "Table '{$table}' exists: {$exists}\n";

        if ($exists === "YES") {
            echo "Columns for '{$table}':\n";
            $columns = DB::select("DESCRIBE {$table}");
            foreach ($columns as $col) {
                echo "  - {$col->Field} ({$col->Type}) | Null: {$col->Null} | Key: {$col->Key}\n";
            }
        }
        echo "---------------------------\n";
    }

    echo "Sample data check:\n";
    echo "Companies count: " . DB::table('companies')->count() . "\n";
    echo "Locations count: " . DB::table('locations')->count() . "\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
