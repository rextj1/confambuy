<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_are_seeded_correctly()
    {
        // Arrange: Create a category to ensure the seeder has something to work with
        $category = Category::factory()->create();

        // Act: Run the seeder
        $this->seed(ProductSeeder::class);

        // Assert: Verify Products were created
        // The seeder creates 5 products per category found
        $products = Product::where('category_id', $category->id)->get();
        $this->assertCount(5, $products, 'Expected 5 products to be seeded for the category.');

        foreach ($products as $product) {
            // Verify SKUs exist
            $this->assertNotEmpty($product->skus, "Product {$product->id} should have SKUs.");

            // Verify Images exist (Seeder creates 3 images per product)
            $this->assertCount(3, $product->images, "Product {$product->id} should have 3 images.");

            // Verify Inventory exists for each SKU
            foreach ($product->skus as $sku) {
                $this->assertNotNull($sku->inventory, "SKU {$sku->sku} should have an inventory record.");
                $this->assertEquals($sku->id, $sku->inventory->product_sku_id);
            }

            // Verify stock_quantity matches the sum of inventory
            $totalStock = $product->skus->sum(fn ($sku) => $sku->inventory->quantity);
            $this->assertEquals($totalStock, $product->stock_quantity, "Product {$product->id} stock quantity mismatch.");
        }
    }
}