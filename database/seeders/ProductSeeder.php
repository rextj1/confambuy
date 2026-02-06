<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

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

                    collect([0, 1, 2])->each(function (int $position) use ($product): void {
                        $file = UploadedFile::fake()->image("product-{$product->id}-{$position}.jpg");

                        $product->addMedia($file)
                            ->usingName($product->name)
                            ->withCustomProperties([
                                'alt' => $product->name,
                                'is_featured' => $position === 0,
                                'position' => $position,
                            ])
                            ->toMediaCollection('images');
                    });
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
