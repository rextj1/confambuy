<?php

namespace Database\Factories;

use App\Models\ProductSku;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_sku_id' => ProductSku::factory(),
            'quantity' => fake()->numberBetween(0, 100),
            'reserved' => 0,
            'location' => fake()->optional()->word(),
            'low_stock_threshold' => 5,
            'allow_backorder' => false,
            'stock_status' => 'in_stock',
        ];
    }
}
