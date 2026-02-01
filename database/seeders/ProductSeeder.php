<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::whereNotNull('parent_id')->get();

        if ($categories->isEmpty()) {
            $categories = Category::all();
        }

        foreach ($categories as $category) {
            // Create 5 products per subcategory
            for ($i = 0; $i < 5; $i++) {
                $name = fake()->words(3, true);
                $price = fake()->randomFloat(2, 10, 500);

                $product = Product::create([
                    'category_id' => $category->id,
                    'name' => ucfirst($name),
                    'slug' => Str::slug($name) . '-' . Str::random(6),
                    'description' => fake()->paragraph(),
                    'price' => $price,
                    'sale_price' => fake()->boolean(30) ? $price * 0.8 : null,
                    'sku' => strtoupper(Str::random(8)),
                    'stock_quantity' => 0, // Will be summed from SKUs
                    'is_active' => true,
                    'is_featured' => fake()->boolean(20),
                ]);

                // Create SKUs (Variants)
                $this->createSkusForProduct($product);

                // Add Product Images
                for ($j = 0; $j < 3; $j++) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => 'https://placehold.co/600x400?text=' . urlencode($product->name),
                        'alt' => $product->name,
                        'position' => $j,
                    ]);
                }
            }
        }
    }

    private function createSkusForProduct(Product $product)
    {
        // Simplified logic: Create 1-3 variants per product
        $variantCount = rand(1, 3);
        $totalStock = 0;

        for ($k = 0; $k < $variantCount; $k++) {
            $skuCode = $product->sku . '-' . ($k + 1);
            $price = $product->price + rand(-5, 15);

            $sku = $product->skus()->create([
                'sku' => $skuCode,
                'title' => $product->name . ' - Variant ' . ($k + 1),
                'price' => $price,
                'cost' => $price * 0.6,
                'weight' => fake()->randomFloat(2, 0.1, 5),
                'attributes' => ['Color' => fake()->safeColorName(), 'Size' => fake()->randomElement(['S', 'M', 'L'])],
                'active' => true,
                'manage_stock' => true,
                'length' => fake()->randomFloat(2, 10, 50),
                'width' => fake()->randomFloat(2, 10, 50),
                'height' => fake()->randomFloat(2, 10, 50),
            ]);

            // Create Inventory
            $qty = rand(0, 100);
            Inventory::create([
                'product_sku_id' => $sku->id,
                'quantity' => $qty,
                'reserved' => 0,
                'location' => 'Warehouse A',
                'low_stock_threshold' => 5,
                'allow_backorder' => false,
            ]);

            $totalStock += $qty;
        }

        $product->update(['stock_quantity' => $totalStock]);
    }
}