<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSku;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::whereNotNull('parent_id')->get();

        if ($categories->isEmpty()) {
            $categories = Category::all();
        }

        foreach ($categories as $category) {
            $productsPerCategory = 6;

            Product::factory()
                ->count($productsPerCategory)
                ->create()
                ->each(function (Product $product) use ($category): void {
                    $isOnSale = fake()->boolean(30);
                    $price = fake()->randomFloat(2, 10, 500);

                    $product->update([
                        'price' => $isOnSale ? $price * 0.8 : $price,
                        'compare_at_price' => $isOnSale ? $price : null,
                        'active' => true,
                        'featured' => fake()->boolean(20),
                    ]);

                    $product->categories()->sync([$category->id]);

                    $this->createSkusForProduct($product);

                    ProductImage::factory()
                        ->count(3)
                        ->sequence(
                            ['position' => 0],
                            ['position' => 1],
                            ['position' => 2]
                        )
                        ->create([
                            'product_id' => $product->id,
                            'path' => 'https://placehold.co/600x400?text='.urlencode($product->name),
                            'alt' => $product->name,
                        ]);
                });
        }
    }

    private function createSkusForProduct(Product $product): void
    {
        // Simplified logic: Create 1-3 variants per product
        $variantCount = rand(1, 3);
        $skus = ProductSku::factory()
            ->count($variantCount)
            ->create(['product_id' => $product->id]);

        foreach ($skus as $index => $sku) {
            $sku->update([
                'sku' => $product->sku.'-'.($index + 1),
                'title' => $product->name.' - Variant '.($index + 1),
                'price' => $product->price + rand(-5, 15),
                'cost' => $product->price * 0.6,
            ]);

            Inventory::factory()->create([
                'product_sku_id' => $sku->id,
                'quantity' => rand(0, 100),
                'reserved' => 0,
                'location' => 'Warehouse A',
                'low_stock_threshold' => 5,
                'allow_backorder' => false,
            ]);
        }
    }
}
