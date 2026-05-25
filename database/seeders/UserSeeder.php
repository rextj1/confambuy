<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | ROLES
        |--------------------------------------------------------------------------
        */
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $customerRole = Role::where('name', 'customer')->first();

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN (OWNER)
        |--------------------------------------------------------------------------
        */

        $owner = User::firstOrCreate(
            [
                'email' => config('super_admin.email'),
            ],
            [
                'name' => config('super_admin.name'),
                'password' => Hash::make(config('super_admin.password')),
                'phone' => 12345678912,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($superAdminRole) {
            $owner->syncRoles([$superAdminRole->name]);
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE SINGLE VENDOR (LINKED TO OWNER)
        |--------------------------------------------------------------------------
        */

        Vendor::firstOrCreate(
            [
                'user_id' => $owner->id,
            ],
            [
                'name' => config('super_admin.store_name', 'My Store'),
                'slug' => Str::slug(config('super_admin.store_name', 'My Store')),
                'contact_email' => config('super_admin.email'),
                'phone_number' => 12345678912,
                'is_active' => true,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | ADMIN USER (STORE MANAGER)
        |--------------------------------------------------------------------------
        */

        $admin = User::firstOrCreate(
            [
                'email' => config('admin.email', 'admin@confambuy.com'),
            ],
            [
                'name' => config('admin.name', 'Admin User'),
                'password' => Hash::make(config('admin.password', 'password')),
                'phone' => 12345678923,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($adminRole) {
            $admin->syncRoles([$adminRole->name]);
        }

        /*
        |--------------------------------------------------------------------------
        | CUSTOMERS
        |--------------------------------------------------------------------------
        */

        $users = User::factory(20)->create();

        foreach ($users as $user) {
            if ($customerRole) {
                $user->assignRole($customerRole->name);
            }

            Address::factory()->create([
                'user_id' => $user->id,
                'name' => $user->name,
                'default_shipping' => true,
                'default_billing' => true,
            ]);
        }
    }
}