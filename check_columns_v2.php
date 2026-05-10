<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "WALLET_TRANSACTIONS:\n";
print_r(Schema::getColumnListing('wallet_transactions'));
echo "SESSIONS:\n";
print_r(Schema::getColumnListing('sessions'));
