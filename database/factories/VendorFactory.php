<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendor>
 */
class VendorFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'user_id' => User::factory(), // can override in seeder
            'name' => $name,
            'slug' => Str::slug($name),
            'logo' => null,
            'description' => fake()->paragraph(3),
            'contact_email' => fake()->companyEmail(),
            'phone_number' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'cac_number' => fake()->numerify('RC########'),
            'bank_info' => [
                'bank' => fake()->randomElement(['GTB', 'Access Bank', 'UBA', 'Zenith']),
                'account_number' => fake()->bankAccountNumber(),
                'account_name' => fake()->name(),
            ],
            'is_active' => true,
        ];
    }

    /**
     * Factory state for the single real store (optional)
     */
    public function storeOwner(): static
    {
        return $this->state(fn () => [
            'name' => config('super_admin.store_name'),
            'slug' => Str::slug(config('super_admin.name')),
            'contact_email' => config('super_admin.email'),
            'is_active' => true,
        ]);
    }
}
