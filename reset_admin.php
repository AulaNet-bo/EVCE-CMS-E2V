<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::where('email', 'admin@admin.com')->first();

if (!$user) {
    echo "Creating Admin User...\n";
    $user = User::create([
        'name' => 'Admin',
        'email' => 'admin@admin.com',
        'password' => Hash::make('password'),
    ]);
} else {
    echo "Resetting Admin Password...\n";
    $user->password = Hash::make('password');
    $user->save();
}

echo "Done. User: admin@admin.com / Pass: password\n";
