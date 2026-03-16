<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UpdateRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Define internal administrative roles
        $roles = [
            'staff_admin',
            'accountant',
            'sales',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }
}
