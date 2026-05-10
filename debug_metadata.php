<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tx = \App\Models\WalletTransaction::find(167);
if ($tx) {
    echo json_encode($tx->metadata, JSON_PRETTY_PRINT);
} else {
    echo "Transaction not found";
}
