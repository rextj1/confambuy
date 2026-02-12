<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = config('permission.defaults.guard', 'web');

        // Core roles
        $roles = ['admin', 'staff', 'customer'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => $guard]);
        }

        // Core permissions (scoped by domain)
        $perms = [
            'products.create', 'products.view', 'products.update', 'products.delete',
            'addresses.view', 'addresses.create', 'addresses.update', 'addresses.delete',
            'categories.manage',
            'orders.create', 'orders.view', 'orders.update', 'orders.cancel',
            'shipments.manage',
            'inventory.manage',
            'coupons.manage',
            'users.view', 'users.manage',
            'settings.manage',
            'reviews.moderate',
            'reports.view',
        ];

        foreach ($perms as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
        }

        // Attach some defaults
        $admin = Role::where('name', 'admin')->where('guard_name', $guard)->first();
        if ($admin) {
            $admin->syncPermissions(Permission::where('guard_name', $guard)->get());
        }

        $staff = Role::where('name', 'staff')->where('guard_name', $guard)->first();
        if ($staff) {
            $staff->syncPermissions([
                'products.view',
                'products.create',
                'products.update',
                'categories.manage',
                'orders.view',
                'orders.update',
                'shipments.manage',
                'inventory.manage',
                'coupons.manage',
                'reviews.moderate',
                'reports.view',
            ]);
        }

        $customer = Role::where('name', 'customer')->where('guard_name', $guard)->first();
        if ($customer) {
            $customer->syncPermissions([
                'products.view',
                'addresses.view',
                'addresses.create',
                'addresses.update',
                'addresses.delete',
                'orders.create',
                'orders.view',
                'orders.cancel',
            ]);
        }

        $defaultPassword = Hash::make('password');

        $adminUser = User::updateOrCreate(
            ['email' => 'admin@confambuy.com'],
            [
                'name' => 'Admin User',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $staffUser = User::updateOrCreate(
            ['email' => 'tojurex@gmail.com'],
            [
                'name' => 'Rex',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        if ($adminUser && $admin) {
            $adminUser->syncRoles([$admin->name]);
        }

        if ($staffUser && $staff) {
            $staffUser->syncRoles([$staff->name]);
        }

        if ($customer) {
            User::whereDoesntHave('roles')
                ->whereNotIn('email', ['admin@confambuy.com', 'tojurex@gmail.com'])
                ->get()
                ->each(function (User $user) use ($customer): void {
                    $user->assignRole($customer->name);
                });
        }
    }
}
