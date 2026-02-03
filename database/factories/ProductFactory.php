<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function ($product): void {
            if ($product->categories()->count() === 0) {
                $product->categories()->attach(Category::factory()->create()->id);
            }
        });
    }

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $price = fake()->randomFloat(2, 10, 500);
        $compareAtPrice = fake()->boolean(30)
            ? fake()->randomFloat(2, $price, $price + 200)
            : null;

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'sku' => fake()->unique()->bothify('PROD-####-????'),
            'description' => fake()->paragraph(),
            'price' => $price,
            'compare_at_price' => $compareAtPrice,
            'active' => fake()->boolean(90),
            'featured' => fake()->boolean(10),
            'taxable' => true,
            'published_at' => now(),
            'metadata' => ['brand' => fake()->company(), 'material' => fake()->word()],
        ];
    }
}
