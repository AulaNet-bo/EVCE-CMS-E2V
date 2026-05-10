<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Wallet;

$email = 'jorge.ps.bo@gmail.com';
$u = User::with('company', 'wallet')->where('email', $email)->first();

if (!$u) {
    die("User not found: $email\n");
}

echo "USER DETAILS:\n";
echo "ID: {$u->id}\n";
echo "Name: {$u->name}\n";
echo "Email: {$u->email}\n";
echo "Company: " . ($u->company->name ?? 'None') . "\n";
echo "Billing Doc: {$u->billing_document} ({$u->billing_doc_type})\n";
echo "Billing RS: {$u->billing_razon_social}\n";

if ($u->wallet) {
    echo "\nWALLET DETAILS:\n";
    echo "ID: {$u->wallet->id}\n";
    echo "Balance: {$u->wallet->balance} {$u->wallet->currency}\n";
    echo "Is Postpaid: " . ($u->wallet->is_postpaid ? 'Yes' : 'No') . "\n";
} else {
    echo "\nNO WALLET FOUND\n";
}

echo "\nRECENT SESSIONS:\n";
$sessions = \App\Models\ChargingSession::where('user_id', $u->id)->latest()->take(5)->get();
foreach ($sessions as $s) {
    echo "ID: {$s->id} | Total: {$s->total_cost} | Invoice URL: " . ($s->invoice_url ?: 'None') . " | Created: {$s->created_at}\n";
}
