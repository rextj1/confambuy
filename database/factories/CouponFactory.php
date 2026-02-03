<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['percentage', 'fixed_amount', 'free_shipping']);
        $value = $type === 'percentage'
            ? fake()->numberBetween(5, 30)
            : fake()->numberBetween(500, 5000);

        return [
            'code' => strtoupper(Str::random(8)),
            'type' => $type,
            'value' => $value,
            'min_spend' => fake()->randomFloat(2, 0, 10000),
            'max_discount' => $type === 'percentage' ? fake()->randomFloat(2, 500, 5000) : null,
            'usage_limit' => fake()->optional()->numberBetween(50, 5000),
            'limit_per_user' => fake()->numberBetween(1, 5),
            'used_count' => 0,
            'is_active' => true,
            'starts_at' => now()->subDays(fake()->numberBetween(1, 10)),
            'expires_at' => now()->addDays(fake()->numberBetween(10, 90)),
            'constraints' => null,
            'name' => fake()->optional()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'is_automatic' => fake()->boolean(10),
        ];
    }
}
