<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "SERVER TIME: " . date('Y-m-d H:i:s') . "\n";
    $today = DB::table('wallet_transactions')->whereDate('created_at', date('Y-m-d'))->latest()->get();
    echo "COUNT TODAY: " . count($today) . "\n";
    foreach ($today as $t) {
        echo "ID: {$t->id} | AMOUNT: {$t->amount} | STATUS: {$t->status} | CREATED: {$t->created_at} | PAY_URL: " . (isset($t->payment_url) ? (str_limit($t->payment_url, 40)) : 'N/A') . "\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

function str_limit($val, $limit) {
    return strlen($val) > $limit ? substr($val, 0, $limit) . '...' : $val;
}
