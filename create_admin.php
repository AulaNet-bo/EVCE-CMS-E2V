<?php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::updateOrCreate(
    ['email' => 'admin@admin.com'],
    [
        'name' => 'Admin',
        'password' => Hash::make('admin123'),
    ]
);

echo "SUCCESS: Admin user created/updated (admin@admin.com / admin123)\n";
