<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->productName(); // Requires a provider or use fake()->words(3, true)
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sku' => fake()->unique()->bothify('PROD-####-????'),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 1000, 100000),
            'compare_at_price' => fake()->optional()->randomFloat(2, 100000, 150000),
            'active' => fake()->boolean(90),
            'featured' => fake()->boolean(10),
            'taxable' => true,
            'published_at' => now(),
            'metadata' => ['brand' => fake()->company(), 'material' => fake()->word()],
        ];
    }
}