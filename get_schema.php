<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "USERS:\n";
print_r(Schema::getColumnListing('users'));
echo "\nWALLET_TRANSACTIONS:\n";
print_r(Schema::getColumnListing('wallet_transactions'));
echo "\nCHARGING_SESSIONS:\n";
print_r(Schema::getColumnListing('charging_sessions'));
echo "\nRFID_TAGS:\n";
print_r(Schema::getColumnListing('rfid_tags'));
echo "\nPRODUCTS:\n";
print_r(Schema::getColumnListing('products'));
