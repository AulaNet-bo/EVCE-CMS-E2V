<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Use RAW SQL for Truncate to avoid Eloquent/Schema quirks with Views
try {
    DB::connection('steve')->statement('SET FOREIGN_KEY_CHECKS=0;');
    
    // Check if 'transaction' is a table or view. If view, we can't truncate it directly.
    // In Steve, 'transaction' is often a table, but let's be careful.
    // Based on previous error, it says table doesn't exist? But we just queried it.
    // Ah, maybe case sensitivity? 'Transaction' vs 'transaction'? Windows is case-insensitive usually.
    
    // Try deleting instead of truncate for safety if truncate fails
    DB::connection('steve')->table('connector_meter_value')->delete();
    DB::connection('steve')->table('transaction_stop')->delete();
    DB::connection('steve')->table('transaction_start')->delete();
    
    // If 'transaction' table exists (not view), clean it. 
    // Usually Steve has transaction_start/stop and a transaction table.
    // Let's check tables again to be sure.
    $tables = DB::connection('steve')->select('SHOW TABLES');
    $hasTransactionTable = false;
    foreach($tables as $t) {
        if (strtolower(reset($t)) == 'transaction') $hasTransactionTable = true;
    }
    
    if ($hasTransactionTable) {
        DB::connection('steve')->table('transaction')->delete();
        // Reset Auto Increment
        DB::connection('steve')->statement('ALTER TABLE transaction AUTO_INCREMENT = 1');
    }

    DB::connection('steve')->statement('SET FOREIGN_KEY_CHECKS=1;');
    
    echo "Steve DB Cleaned (via Delete).\n";
    
    // CMS Clean
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    \App\Models\ChargingSession::truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "CMS Cleaned.\n";
    
    // Wallet Reset
    $wallet = \App\Models\Wallet::where('user_id', 1)->first();
    if ($wallet) {
        $wallet->balance = 1000;
        $wallet->save();
        echo "Wallet Reset.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
