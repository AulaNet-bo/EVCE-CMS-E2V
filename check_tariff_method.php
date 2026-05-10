<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if (method_exists(App\Models\Tariff::class, 'resolveForStation')) {
    echo "METHOD EXISTS\n";
} else {
    echo "METHOD DOES NOT EXIST\n";
}

$refl = new ReflectionClass(App\Models\Tariff::class);
echo "File: " . $refl->getFileName() . "\n";
