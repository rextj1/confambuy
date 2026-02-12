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
        // Ensure admin seed is idempotent across repeated runs.
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@confambuy.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'phone' => '1234567890',
                'is_active' => true,
            ]
        );

        // Create deterministic customer records so reruns are safe.
        for ($index = 1; $index <= 20; $index++) {
            $email = sprintf('customer%02d@confambuy.com', $index);
            $customer = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => "Customer {$index}",
                    'password' => Hash::make('password'),
                    'phone' => sprintf('0800000%04d', $index),
                    'is_active' => true,
                ]
            );

            Address::query()->updateOrCreate(
                [
                    'user_id' => $customer->id,
                    'default_shipping' => true,
                    'default_billing' => true,
                ],
                [
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'line_1' => "{$index} Example Street",
                    'city' => 'Lagos',
                    'state' => 'Lagos',
                    'postal_code' => sprintf('10%04d', $index),
                    'country' => 'Nigeria',
                    'phone' => $customer->phone ?? sprintf('0800000%04d', $index),
                ]
            );
        }
    }
}
