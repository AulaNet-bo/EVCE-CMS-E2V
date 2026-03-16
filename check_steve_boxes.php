<?php
use Illuminate\Support\Facades\DB;

try {
    echo "--- Steve Database Check ---\n";
    $boxes = DB::connection('steve')->table('charge_box')->get();
    echo "Found " . count($boxes) . " Charge Boxes:\n";
    foreach ($boxes as $box) {
        $id = $box->charge_box_id ?? 'N/A';
        $status = $box->registration_status ?? 'N/A';
        echo "  - ID: {$id} | Status: {$status}\n";

        $connectors = DB::connection('steve')->table('connector')
            ->where('charge_box_id', $id)
            ->get();
        foreach ($connectors as $conn) {
            echo "    * Connector {$conn->connector_id} (PK: {$conn->connector_pk})\n";
        }
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
