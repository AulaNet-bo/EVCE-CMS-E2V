<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RfidTag;
use Illuminate\Support\Facades\DB;

$tags = RfidTag::all();
$fixedCount = 0;

echo "Aggressive RFID Tag Truncation (Any > 8 -> Last 8)...\n";

foreach ($tags as $tag) {
    $oldCode = $tag->tag_code;
    
    if (strlen($oldCode) > 8) {
        $newCode = substr($oldCode, -8);
        echo "Truncating Tag: $oldCode -> $newCode\n";
        
        try {
            DB::beginTransaction();
            $tag->update(['tag_code' => $newCode]);
            DB::commit();
            $fixedCount++;
        } catch (\Exception $e) {
            DB::rollBack();
            echo "Failed to fix $oldCode: " . $e->getMessage() . "\n";
        }
    }
}

echo "Finished. Fixed $fixedCount tags.\n";
