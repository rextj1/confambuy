<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create Permissions
        $permissions = [
            'view dashboard',
            'manage users',
            'manage settings',
            'manage products',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Create Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        // 3. Assign Permissions to Roles
        // Admin gets all permissions
        $adminRole->syncPermissions(Permission::all());

        // 4. Create the Admin User
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@confambuy.com'],
            [
                'name' => 'Toju Rex',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 5. Assign Role to User
        $adminUser->assignRole($adminRole);
    }
}
