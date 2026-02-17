<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\ChargingSession;
use App\Models\Wallet;

echo "Cleaning up ALL Transaction Data...\n";

try {
    // 1. Clean Steve DB (Transactions & Meter Values)
    echo "Truncating Steve Tables...\n";
    // Order matters due to FKs
    DB::connection('steve')->statement('SET FOREIGN_KEY_CHECKS=0;');
    DB::connection('steve')->table('connector_meter_value')->truncate();
    DB::connection('steve')->table('transaction_stop')->truncate();
    DB::connection('steve')->table('transaction_start')->truncate();
    DB::connection('steve')->table('transaction')->truncate();
    DB::connection('steve')->statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "Steve DB Cleaned.\n";

    // 2. Clean CMS DB (Synced Sessions)
    echo "Truncating CMS Charging Sessions...\n";
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    ChargingSession::truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "CMS Sessions Cleaned.\n";

    // 3. Reset User Wallet Balance (Optional, but good for fresh start)
    // Let's reset Admin wallet to 1000 BOB
    $wallet = Wallet::where('user_id', 1)->first();
    if ($wallet) {
        $wallet->balance = 1000;
        $wallet->save();
        echo "Admin Wallet reset to 1000 BOB.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Cleanup Complete!\n";
