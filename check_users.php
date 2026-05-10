<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $count = DB::table('remote_operators')->count();
    $users = DB::table('remote_operators')->get()->pluck('username')->toArray();
    echo "COUNT: " . $count . "\n";
    echo "USERS: " . implode(', ', $users) . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
