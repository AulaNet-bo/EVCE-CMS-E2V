<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tags = App\Models\RfidTag::latest()->take(20)->get(['tag_code']);
foreach ($tags as $tag) {
    echo "TAG: [{$tag->tag_code}] (Length: " . strlen($tag->tag_code) . ")\n";
}
