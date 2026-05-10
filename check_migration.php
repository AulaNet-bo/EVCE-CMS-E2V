<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$migration = DB::table('migrations')->where('migration', 'like', '%canal_caja%')->first();
print_r($migration);
