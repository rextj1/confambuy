<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@confambuy.com',
            'password' => Hash::make('password'),
            'phone' => '1234567890',
            'is_active' => true,
        ]);

        // Create Customers
        $users = User::factory(20)->create([
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Create Addresses for Customers
        foreach ($users as $user) {
            Address::factory()->create([
                'user_id' => $user->id,
                'name' => $user->name,
                'default_shipping' => true,
                'default_billing' => true,
            ]);
        }
    }
}
