<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cached permissions to ensure test isolation
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_can_list_products()
    {
        // Create 5 active products
        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                        'category' => ['id', 'name'],
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_can_view_single_product()
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                ],
            ]);
    }

    public function test_can_filter_products_by_category_slug()
    {
        $category = Category::factory()->create(['slug' => 'electronics']);
        $matchedProduct = Product::factory()->create();
        $matchedProduct->categories()->sync([$category->id]);

        $otherProduct = Product::factory()->create();

        $response = $this->getJson('/api/v1/products?filter[category]=electronics');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $matchedProduct->id,
            ])
            ->assertJsonMissing([
                'id' => $otherProduct->id,
            ]);
    }

    public function test_can_sort_products_by_price_desc()
    {
        $cheap = Product::factory()->create(['price' => 10.00]);
        $expensive = Product::factory()->create(['price' => 100.00]);

        $response = $this->getJson('/api/v1/products?sort=-price');

        $response->assertStatus(200);

        $this->assertSame(
            $expensive->id,
            $response->json('data.0.id')
        );

        $this->assertSame(
            $cheap->id,
            $response->json('data.1.id')
        );
    }

    public function test_can_filter_products_by_category_name()
    {
        $category = Category::factory()->create(['name' => 'Electronics']);
        $matchedProduct = Product::factory()->create();
        $matchedProduct->categories()->sync([$category->id]);

        $otherProduct = Product::factory()->create();

        $response = $this->getJson('/api/v1/products?filter[category]=Electronics');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $matchedProduct->id,
            ])
            ->assertJsonMissing([
                'id' => $otherProduct->id,
            ]);
    }

    public function test_can_filter_products_by_price_range()
    {
        $cheap = Product::factory()->create(['price' => 10.00]);
        $mid = Product::factory()->create(['price' => 50.00]);
        $expensive = Product::factory()->create(['price' => 100.00]);

        $response = $this->getJson('/api/v1/products?filter[price_min]=20&filter[price_max]=80');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $mid->id])
            ->assertJsonMissing(['id' => $cheap->id])
            ->assertJsonMissing(['id' => $expensive->id]);
    }

    public function test_per_page_is_capped_at_100()
    {
        Product::factory()->count(150)->create();

        $response = $this->getJson('/api/v1/products?per_page=500');

        $response->assertStatus(200)
            ->assertJsonCount(100, 'data');
    }

    public function test_admin_can_create_product()
    {
        // Setup admin user with permissions
        $user = User::factory()->create();
        $role = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'products.create']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        Sanctum::actingAs($user);

        $category = Category::factory()->create();

        $payload = [
            'category_id' => $category->id,
            'name' => 'New Test Product',
            'description' => 'This is a description.',
            'price' => 150.00,
            'stock_quantity' => 20,
            'is_active' => true,
            'sku' => 'TEST-SKU-001',
        ];

        $response = $this->postJson('/api/v1/products', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'New Test Product']);

        $this->assertDatabaseHas('products', ['sku' => 'TEST-SKU-001']);
    }

    public function test_regular_user_cannot_create_product()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = Product::factory()->make()->toArray();

        $response = $this->postJson('/api/v1/products', $payload);

        $response->assertStatus(403); // Forbidden
    }

    public function test_admin_can_update_product()
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'products.update']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        Sanctum::actingAs($user);

        $product = Product::factory()->create();

        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'name' => 'Updated Product Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Product Name']);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Product Name']);
    }

    public function test_admin_can_delete_product()
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'products.delete']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        Sanctum::actingAs($user);

        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertSuccessful(); // 200 or 204

        $this->assertSoftDeleted($product);
    }
}
