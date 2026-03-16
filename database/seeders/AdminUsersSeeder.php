<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUsersSeeder extends Seeder
{
    public function run(): void
    {
        $adminUsers = [
            [
                'name' => 'Staff Administrative',
                'email' => 'staff@evce.com',
                'password' => 'staff123',
                'role' => 'staff_admin',
            ],
            [
                'name' => 'Accountant Manager',
                'email' => 'contabilidad@evce.com',
                'password' => 'conta123',
                'role' => 'accountant',
            ],
            [
                'name' => 'Sales & Operations',
                'email' => 'ventas@evce.com',
                'password' => 'ventas123',
                'role' => 'sales',
            ],
        ];

        foreach ($adminUsers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                ]
            );

            $user->syncRoles([$data['role']]);
        }
    }
}
