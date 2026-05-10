<?php
putenv('DB_HOST=127.0.0.1');
$_ENV['DB_HOST'] = '127.0.0.1';

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Permission;

$u = User::find(1);
echo "User: " . $u->name . "\n";
echo "Roles: " . $u->getRoleNames()->implode(', ') . "\n";

echo "\nPermissions related to Qr:\n";
$perms = Permission::where('name', 'like', '%qr%')->get();
foreach ($perms as $p) {
    echo " - " . $p->name . "\n";
}

echo "\nIs 'App\Filament\Pages\DispenserQrGenerator' discoverable?\n";
$pages = \Filament\Facades\Filament::getPages();
foreach ($pages as $page) {
    echo " - " . $page . "\n";
}
