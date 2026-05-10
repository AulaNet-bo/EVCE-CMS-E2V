<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\RfidTag;
use App\Models\ChargingSession;

$names = ['Julio Cesar Peredo Flores', 'Mariela Justiniano'];

foreach ($names as $name) {
    echo "--- CHECKING USER: $name ---\n";
    $u = User::where('name', 'like', "%$name%")->first();
    if (!$u) {
        echo "User not found.\n";
        continue;
    }

    echo "User ID: {$u->id} | Email: {$u->email}\n";
    echo "Wallet Balance: " . ($u->wallet->balance ?? 'N/A') . "\n";

    echo "RFID Tags:\n";
    $tags = RfidTag::where('user_id', $u->id)->get();
    foreach ($tags as $t) {
        echo " - Tag: [{$t->tag_code}] | Active: " . ($t->is_active ? 'Yes' : 'No') . "\n";
    }

    echo "Recent Sessions (last 5):\n";
    $sessions = ChargingSession::where('user_id', $u->id)->latest()->take(5)->get();
    foreach ($sessions as $s) {
        echo " - ID: {$s->id} | Status: {$s->status} | TX: {$s->transaction_id} | Cost: {$s->total_cost} | Energy: {$s->total_energy_kwh} | Created: {$s->created_at}\n";
    }
    echo "\n";
}

echo "--- GLOBAL RECENT SESSIONS (LAST 10) ---\n";
$allSessions = ChargingSession::latest()->take(10)->get();
foreach ($allSessions as $s) {
    echo "ID: {$s->id} | User: " . ($s->user->name ?? 'Guest') . " | Status: {$s->status} | TX: {$s->transaction_id} | Created: {$s->created_at}\n";
}
