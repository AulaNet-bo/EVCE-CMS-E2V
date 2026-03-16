<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class RoleInitializerSeeder extends Seeder
{
    public function run(): void
    {
        // Define roles
        $roles = ['super_admin', 'enterprise_admin', 'client', 'system_accountant'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Create Super Admin (Maestro Total)
        $admin = User::updateOrCreate(
            ['email' => 'admin@evce.com'],
            [
                'name' => 'Maestro Total',
                'password' => Hash::make('password123'),
                'is_admin' => true,
            ]
        );

        $admin->syncRoles(['super_admin']);

        $this->command->info('Roles initialized and Super Admin created (admin@evce.com / password123)');
    }
}
