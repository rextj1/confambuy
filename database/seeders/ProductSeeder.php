<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $productsPerCategory = max(1, (int) config('seeding.product.products_per_category', 6));
        $generateMedia = (bool) config('seeding.product.generate_media', true);
        $minSkus = max(1, (int) config('seeding.product.min_skus_per_product', 1));
        $maxSkus = max($minSkus, (int) config('seeding.product.max_skus_per_product', 3));

        $categories = Category::whereNotNull('parent_id')->get();

        if ($categories->isEmpty()) {
            $categories = Category::all();
        }

        $categoriesLimit = config('seeding.product.categories_limit');

        if (is_numeric($categoriesLimit) && (int) $categoriesLimit > 0) {
            $categories = $categories->take((int) $categoriesLimit)->values();
        }

        if ($this->command) {
            $this->command->getOutput()->writeln(
                sprintf(
                    '  ProductSeeder config: categories=%d, products/category=%d, media=%s, skus=%d-%d',
                    $categories->count(),
                    $productsPerCategory,
                    $generateMedia ? 'on' : 'off',
                    $minSkus,
                    $maxSkus
                )
            );
        }

        foreach ($categories as $category) {
            for ($index = 1; $index <= $productsPerCategory; $index++) {
                $name = "{$category->name} Product {$index}";
                $slug = Str::slug("{$category->slug}-product-{$index}");
                $basePrice = fake()->randomFloat(2, 10, 500);
                $isOnSale = fake()->boolean(30);
                $skuCode = sprintf('CAT-%d-PROD-%03d', $category->id, $index);

                $product = Product::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'sku' => $skuCode,
                        'description' => fake()->paragraph(),
                        'price' => $isOnSale ? round($basePrice * 0.8, 2) : $basePrice,
                        'compare_at_price' => $isOnSale ? $basePrice : null,
                        'active' => true,
                        'featured' => fake()->boolean(20),
                        'taxable' => true,
                        'published_at' => now(),
                        'metadata' => [
                            'brand' => fake()->company(),
                            'material' => fake()->word(),
                        ],
                    ]
                );

                $product->categories()->syncWithoutDetaching([$category->id]);

                if (! $product->skus()->exists()) {
                    $this->createSkusForProduct($product, $minSkus, $maxSkus);
                }

                if ($generateMedia && $product->getMedia('images')->isEmpty()) {
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
                }
            }
        }
    }

    private function createSkusForProduct(Product $product, int $minSkus, int $maxSkus): void
    {
        // Simplified logic: Create configurable number of variants per product
        $variantCount = rand($minSkus, $maxSkus);
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
