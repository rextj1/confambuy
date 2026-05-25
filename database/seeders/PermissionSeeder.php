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

        /*
        |--------------------------------------------------------------------------
        | ROLES
        |--------------------------------------------------------------------------
        */
        $roles = [
            'super_admin',
            'admin',
            'staff',
            'customer',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => $guard,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | PERMISSIONS
        |--------------------------------------------------------------------------
        */
        $perms = [
            'products.create', 'products.view', 'products.update', 'products.delete',
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
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => $guard,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ROLE PERMISSION ASSIGNMENTS
        |--------------------------------------------------------------------------
        */

        $superAdmin = Role::where('name', 'super_admin')->first();
        $admin = Role::where('name', 'admin')->first();
        $staff = Role::where('name', 'staff')->first();
        $customer = Role::where('name', 'customer')->first();

        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        if ($admin) {
            $admin->syncPermissions([
                'products.create',
                'products.view',
                'products.update',
                'products.delete',
                'categories.manage',
                'orders.view',
                'orders.update',
                'shipments.manage',
                'inventory.manage',
                'coupons.manage',
                'users.view',
                'reports.view',
                'reviews.moderate',
            ]);
        }

        if ($staff) {
            $staff->syncPermissions([
                'products.view',
                'products.create',
                'products.update',
                'orders.view',
                'orders.update',
                'inventory.manage',
                'shipments.manage',
                'reviews.moderate',
                'reports.view',
            ]);
        }

        if ($customer) {
            $customer->syncPermissions([
                'products.view',
                'orders.create',
                'orders.view',
                'orders.cancel',
            ]);
        }
    }
}