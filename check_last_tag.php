<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tag = DB::table('rfid_tags')->orderBy('id', 'desc')->first();
echo "LAST_TAG:\n";
print_r($tag);
