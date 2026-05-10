<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RfidTag;
use App\Models\ChargingSession;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

echo "Starting Global Database Cleanup...\n";

try {
    DB::beginTransaction();

    // 1. Delete all transactions and sessions (Start from zero financially)
    echo "Deleting all charging sessions...\n";
    ChargingSession::query()->delete();

    echo "Deleting all wallet transactions...\n";
    WalletTransaction::query()->delete();

    // 2. Identify App Tags (starting with 'A')
    $appTags = RfidTag::where('tag_code', 'LIKE', 'A%')->get();
    $appTagIds = $appTags->pluck('id')->toArray();
    $appUserIds = $appTags->pluck('user_id')->unique()->toArray();

    echo "Preserving " . count($appTags) . " App-initiated tags.\n";

    // 3. Delete physical tags (not starting with 'A')
    echo "Deleting physical RFID tags...\n";
    RfidTag::whereNotIn('id', $appTagIds)->delete();

    // 4. Delete users that don't have an App Tag and are not admins
    echo "Cleaning up non-app users...\n";
    User::whereNotIn('id', $appUserIds)
        ->where('is_admin', false)
        ->delete();

    // 5. Reset balances of preserved wallets to 0 (optional, but requested fresh start)
    echo "Resetting app user balances to 0...\n";
    Wallet::whereIn('user_id', $appUserIds)->update(['balance' => 0]);

    DB::commit();
    echo "SUCCESS: Database cleaned. Only App users and their tags remain.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR during cleanup: " . $e->getMessage() . "\n";
}
