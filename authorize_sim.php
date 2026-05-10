<?php
use Illuminate\Support\Facades\DB;

try {
    echo "--- Authorizing SIMULADOR01 in SteVe ---\n";
    
    // Check if ocpp_tag exists (it does, but let's be sure about the name)
    DB::statement("INSERT INTO charge_box (charge_box_id) VALUES ('SIMULADOR01') ON DUPLICATE KEY UPDATE charge_box_id='SIMULADOR01'");
    DB::statement("INSERT INTO ocpp_tag (id_tag, expiry_date) VALUES ('E2V-TEST-TAG', '2030-01-01 00:00:00') ON DUPLICATE KEY UPDATE id_tag='E2V-TEST-TAG'");
    
    echo "SUCCESS: SIMULADOR01 & E2V-TEST-TAG authorized in SteVe.\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
