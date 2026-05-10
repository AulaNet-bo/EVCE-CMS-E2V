<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$u = User::where('email', 'jorge.ps.bo@gmail.com')->first();
if ($u) {
    echo "Current Name: {$u->name}\n";
    $u->name = 'Jorge Padilla S';
    $u->save();
    echo "New Name: {$u->name}\n";
}
