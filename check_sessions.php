<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ChargingSession;
use App\Models\User;

$user = User::where('email', 'jorge.ps.bo@gmail.com')->first();
if (!$user) {
    echo "User not found\n";
    exit;
}

$sessions = ChargingSession::where('user_id', $user->id)
    ->where('status', 'Starting')
    ->get();

echo "Starting sessions for {$user->email}:\n";
foreach ($sessions as $s) {
    echo "ID: {$s->id} | Created: {$s->created_at} | Status: {$s->status}\n";
}

if ($sessions->isEmpty()) {
    echo "No starting sessions found.\n";
}
