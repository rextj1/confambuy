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

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        config()->set('api_cache.store', 'array');
        config()->set('api_cache.ttl_seconds', 600);
    }

    public function test_can_list_categories(): void
    {
        Category::factory()->create(['name' => 'Electronics', 'slug' => 'electronics']);
        Category::factory()->create(['name' => 'Fashion', 'slug' => 'fashion']);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'parent_id',
                        'is_active',
                        'sort_order',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_can_view_single_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $category->id,
                    'name' => 'Electronics',
                    'slug' => 'electronics',
                ],
            ]);
    }

    public function test_admin_can_create_category(): void
    {
        $admin = $this->createUserWithCategoryManagePermission('admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Phones',
            'description' => 'Smartphones and feature phones',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Phones']);

        $this->assertDatabaseHas('categories', [
            'name' => 'Phones',
            'slug' => 'phones',
        ]);
    }

    public function test_customer_cannot_create_category(): void
    {
        $customer = User::factory()->create();
        $role = Role::create(['name' => 'customer']);
        $customer->assignRole($role);

        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Blocked Category',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_category(): void
    {
        $admin = $this->createUserWithCategoryManagePermission('admin');
        Sanctum::actingAs($admin);

        $category = Category::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
        ]);

        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'New Name']);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Name',
            'slug' => 'new-name',
        ]);
    }

    public function test_admin_can_delete_category(): void
    {
        $admin = $this->createUserWithCategoryManagePermission('admin');
        Sanctum::actingAs($admin);

        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_slug_generation_handles_soft_deleted_slug_collision(): void
    {
        $admin = $this->createUserWithCategoryManagePermission('admin');
        Sanctum::actingAs($admin);

        $existing = Category::factory()->create([
            'name' => 'Phones',
            'slug' => 'phones',
        ]);
        $existing->delete();

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Phones',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'phones-1');
    }

    public function test_cannot_set_soft_deleted_parent_category(): void
    {
        $admin = $this->createUserWithCategoryManagePermission('admin');
        Sanctum::actingAs($admin);

        $parent = Category::factory()->create();
        $parent->delete();

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Child Category',
            'parent_id' => $parent->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_can_include_children_and_products_on_category_show(): void
    {
        $parent = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);
        $child = Category::factory()->create([
            'name' => 'Phones',
            'slug' => 'phones',
            'parent_id' => $parent->id,
        ]);
        $product = Product::factory()->create();
        $product->categories()->sync([$parent->id]);

        $response = $this->getJson("/api/v1/categories/{$parent->id}?include=children,products");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $child->id, 'slug' => 'phones'])
            ->assertJsonFragment(['id' => $product->id]);
    }

    public function test_cannot_assign_descendant_as_parent(): void
    {
        $admin = $this->createUserWithCategoryManagePermission('admin');
        Sanctum::actingAs($admin);

        $root = Category::factory()->create(['name' => 'Root', 'slug' => 'root']);
        $child = Category::factory()->create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $root->id,
        ]);

        $response = $this->putJson("/api/v1/categories/{$root->id}", [
            'parent_id' => $child->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_explicit_slug_is_normalized_to_url_friendly_format(): void
    {
        $admin = $this->createUserWithCategoryManagePermission('admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Home Appliances',
            'slug' => '  Home Appliances @ 2026  ',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'home-appliances-at-2026');
    }

    public function test_categories_cache_is_invalidated_after_api_update(): void
    {
        $category = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $this->getJson('/api/v1/categories')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Electronics']);

        Category::query()->whereKey($category->id)->update(['name' => 'Direct Rename']);

        $this->getJson('/api/v1/categories')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Electronics'])
            ->assertJsonMissing(['name' => 'Direct Rename']);

        $admin = $this->createUserWithCategoryManagePermission('admin');
        Sanctum::actingAs($admin);

        $this->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'API Rename',
        ])->assertStatus(200);

        $this->getJson('/api/v1/categories')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'API Rename'])
            ->assertJsonMissing(['name' => 'Electronics']);
    }

    public function test_categories_cache_is_invalidated_after_model_update(): void
    {
        $category = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $this->getJson('/api/v1/categories')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Electronics']);

        $category->update(['name' => 'Model Rename']);

        $this->getJson('/api/v1/categories')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Model Rename'])
            ->assertJsonMissing(['name' => 'Electronics']);
    }

    private function createUserWithCategoryManagePermission(string $roleName): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => $roleName]);
        $permission = Permission::create(['name' => 'categories.manage']);

        $role->givePermissionTo($permission);
        $user->assignRole($role);

        return $user;
    }
}
