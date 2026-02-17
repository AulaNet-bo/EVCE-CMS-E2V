<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Cleaning Tables...\n";
    DB::connection('steve')->statement('SET FOREIGN_KEY_CHECKS=0;');
    
    // 1. Logs
    DB::connection('steve')->table('connector_meter_value')->truncate();
    echo "- Meter Values Cleaned.\n";
    
    // 2. Stop/Start events (This is the source of truth for Steve usually)
    DB::connection('steve')->table('transaction_stop')->truncate();
    echo "- Tx Stop Cleaned.\n";
    
    DB::connection('steve')->table('transaction_start')->truncate();
    echo "- Tx Start Cleaned.\n";
    
    // 'transaction' seems to be a VIEW in this version of Steve, so we can't delete from it directly.
    // Cleaning start/stop tables should automatically empty the view.
    
    DB::connection('steve')->statement('SET FOREIGN_KEY_CHECKS=1;');
    
    // 3. CMS Clean
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    \App\Models\ChargingSession::truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "- CMS Sessions Cleaned.\n";
    
    // 4. Wallet
    $wallet = \App\Models\Wallet::where('user_id', 1)->first();
    if ($wallet) {
        $wallet->balance = 1000;
        $wallet->save();
        echo "- Wallet Reset to 1000.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
