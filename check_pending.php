<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $pending = DB::table('wallet_transactions')->where('status', 'PENDING')->latest()->limit(5)->get();
    foreach ($pending as $p) {
        echo "ID: {$p->id} | AMOUNT: {$p->amount} | REF: " . ($p->reference_id ?? $p->reference ?? 'N/A') . " | WALLET: {$p->wallet_id}\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
