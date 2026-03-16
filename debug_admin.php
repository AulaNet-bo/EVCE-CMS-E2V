<?php
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

$email = 'admin@evce.com';
$user = User::where('email', $email)->first();

if (!$user) {
    echo "User $email not found. Creating...\n";
    $user = User::create([
        'name' => 'Maestro Total',
        'email' => $email,
        'password' => Hash::make('password123'),
        'is_admin' => true,
    ]);
} else {
    echo "User found: ID " . $user->id . "\n";
    $user->update([
        'is_admin' => true,
        'password' => Hash::make('password123'), // Resetting to be sure
    ]);
}

// Ensure super_admin role exists and is assigned
$role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
$user->syncRoles([$role]);

echo "Admin user $email synchronized and password reset to password123. Role assigned: " . $role->name . "\n";
