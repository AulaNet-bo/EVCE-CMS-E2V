<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Wallet;
use App\Models\User;

$userId = 1; // Assuming Admin
$wallet = Wallet::firstOrCreate(['user_id' => $userId]);

echo "User: " . ($wallet->user->name ?? 'Unknown') . "\n";
echo "Initial Balance: " . $wallet->balance . " " . $wallet->currency . "\n";
