<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'order_id' => null,
            'rating' => fake()->numberBetween(1, 5),
            'title' => fake()->optional()->sentence(),
            'body' => fake()->optional()->paragraph(),
            'images' => [],
            'is_approved' => true,
            'helpful_count' => fake()->numberBetween(0, 20),
            'ip_address' => fake()->ipv4(),
            'approved_at' => now(),
            'is_featured' => fake()->boolean(5),
        ];
    }
}
