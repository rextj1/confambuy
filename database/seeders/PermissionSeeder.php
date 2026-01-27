<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Core roles
        $roles = ['super-admin', 'admin', 'inventory', 'orders', 'support', 'marketing'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Example permissions
        $perms = [
            'products.create', 'products.view', 'products.update', 'products.delete',
            'orders.manage', 'orders.view', 'shipments.manage',
            'inventory.manage',
            'coupons.manage',
        ];

        foreach ($perms as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Attach some defaults
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo(Permission::all());
        }

        $super = Role::where('name', 'super-admin')->first();
        if ($super) {
            $super->givePermissionTo(Permission::all());
        }
    }
}
