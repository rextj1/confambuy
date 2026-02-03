<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductSkuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => fake()->unique()->bothify('SKU-####-????'),
            'barcode' => fake()->unique()->ean13(),
            'title' => fake()->word(), // e.g., "Large Red"
            'price' => fake()->randomFloat(2, 1000, 100000),
            'cost' => fake()->randomFloat(2, 500, 50000),
            'weight' => fake()->randomFloat(3, 0.1, 10),
            'length' => fake()->randomFloat(2, 10, 100),
            'width' => fake()->randomFloat(2, 10, 100),
            'height' => fake()->randomFloat(2, 10, 100),
            'manage_stock' => true,
            'active' => true,
            'attributes' => ['color' => fake()->colorName(), 'size' => 'L'],
        ];
    }
}