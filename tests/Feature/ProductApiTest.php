<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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

        $response = $this->getJson('/api/products');

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
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    public function test_can_view_single_product()
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                ]
            ]);
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

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'New Test Product']);

        $this->assertDatabaseHas('products', ['sku' => 'TEST-SKU-001']);
    }

    public function test_regular_user_cannot_create_product()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = Product::factory()->make()->toArray();

        $response = $this->postJson('/api/products', $payload);

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

        $response = $this->putJson("/api/products/{$product->id}", [
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

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertSuccessful(); // 200 or 204

        $this->assertSoftDeleted($product);
    }
}