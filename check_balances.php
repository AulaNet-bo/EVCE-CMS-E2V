<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$users = User::with('wallet')->get();
foreach ($users as $user) {
    echo "User: {$user->email} | Balance: " . ($user->wallet->balance ?? 'N/A') . "\n";
}
