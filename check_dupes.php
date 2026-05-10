<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$dupes = DB::table('users')
    ->select('billing_document', DB::raw('count(*) as count'))
    ->whereNotNull('billing_document')
    ->where('billing_document', '!=', '')
    ->groupBy('billing_document')
    ->having('count', '>', 1)
    ->get();

if ($dupes->isEmpty()) {
    echo "NO_DUPES\n";
} else {
    echo "DUPES_FOUND:\n";
    print_r($dupes->toArray());
}
