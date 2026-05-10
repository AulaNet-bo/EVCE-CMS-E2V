<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RfidTag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$tags = RfidTag::all();
$fixedCount = 0;

echo "Starting RFID Tag Standardization (14 -> 8 chars)...\n";

foreach ($tags as $tag) {
    $oldCode = $tag->tag_code;
    $newCode = $oldCode;

    if (strlen($oldCode) === 14) {
        if (str_starts_with($oldCode, '000000')) {
            $newCode = substr($oldCode, 6);
            echo "Padded Tag found: $oldCode -> $newCode\n";
        } else {
            // It's a genuine 14-char tag, but user says they only want 8.
            // We'll keep it for now unless we are sure which 8 to take.
            echo "Genuine 14-char tag skipped: $oldCode\n";
            continue; 
        }
    } else {
        continue;
    }

    if ($newCode !== $oldCode) {
        try {
            DB::beginTransaction();
            
            // 1. Update in CMS
            $tag->update(['tag_code' => $newCode]);

            // 2. Update in SteVe (we use the observer but let's be explicit if needed)
            // The observer should handle it on 'updated', but let's check
            
            DB::commit();
            $fixedCount++;
            echo "Successfully fixed $oldCode to $newCode\n";
        } catch (\Exception $e) {
            DB::rollBack();
            echo "Failed to fix $oldCode: " . $e->getMessage() . "\n";
        }
    }
}

echo "Finished. Fixed $fixedCount tags.\n";
