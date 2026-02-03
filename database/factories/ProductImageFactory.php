<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductImage>
 */
class ProductImageFactory extends Factory
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
            'product_sku_id' => null,
            'path' => fake()->imageUrl(600, 400),
            'alt' => fake()->optional()->sentence(3),
            'position' => fake()->numberBetween(0, 5),
            'is_featured' => false,
        ];
    }
}
