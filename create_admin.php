<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::firstOrCreate(
    ['email' => 'admin@admin.com'],
    [
        'name' => 'Super Admin',
        'password' => Hash::make('password'),
    ]
);

// If Shield is active, assign super_admin role if exists, otherwise basic access
// For now, just ensuring the user exists. Filament usually allows the first user or local users.
echo "User created: " . $user->email . "\n";
