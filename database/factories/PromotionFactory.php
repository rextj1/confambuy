<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);
        $startsAt = now()->subDays(fake()->numberBetween(1, 5));

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->optional()->sentence(),
            'type' => fake()->randomElement(['percentage', 'fixed_amount', 'buy_x_get_y']),
            'value' => fake()->randomFloat(2, 5, 50),
            'banner_url' => fake()->optional()->imageUrl(1200, 400),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addDays(fake()->numberBetween(5, 30)),
            'is_active' => true,
            'priority' => fake()->numberBetween(0, 10),
        ];
    }
}
