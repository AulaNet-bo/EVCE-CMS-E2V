<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::table('migrations')->where('migration', 'LIKE', '2026_05_05_%')->delete();
echo "Migration records deleted. Ready to re-run.\n";
