<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\ProductSku;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_customer_can_add_item_and_view_cart(): void
    {
        $user = $this->createCustomer();
        $sku = ProductSku::factory()->create(['price' => 500.00]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/cart/items', [
            'sku_id' => $sku->id,
            'quantity' => 2,
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'subtotal' => '1000.00',
            ]);

        $this->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'items',
                ],
            ]);
    }

    public function test_customer_can_update_item_quantity(): void
    {
        $user = $this->createCustomer();
        $sku = ProductSku::factory()->create(['price' => 250.00]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/items', [
            'sku_id' => $sku->id,
            'quantity' => 1,
        ]);

        $cart = $user->carts()->first();
        $item = $cart->items()->first();

        $response = $this->patchJson("/api/v1/cart/items/{$item->id}", [
            'quantity' => 4,
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'subtotal' => '1000.00',
            ]);
    }

    public function test_customer_can_remove_item(): void
    {
        $user = $this->createCustomer();
        $sku = ProductSku::factory()->create(['price' => 200.00]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/items', [
            'sku_id' => $sku->id,
            'quantity' => 1,
        ]);

        $cart = $user->carts()->first();
        $item = $cart->items()->first();

        $this->deleteJson("/api/v1/cart/items/{$item->id}")
            ->assertOk()
            ->assertJsonFragment([
                'subtotal' => '0.00',
            ]);
    }

    public function test_customer_can_update_cart_with_coupon_and_shipping_address(): void
    {
        config([
            'pricing.tax_zones' => ['default' => 0.0],
            'pricing.shipping.zones' => [],
        ]);

        $user = $this->createCustomer();
        $sku = ProductSku::factory()->create(['price' => 1000.00]);
        $address = Address::factory()->create(['user_id' => $user->id]);

        Coupon::factory()->create([
            'code' => 'SAVE50',
            'type' => 'fixed_amount',
            'value' => 50.00,
            'min_spend' => 0,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/items', [
            'sku_id' => $sku->id,
            'quantity' => 1,
        ]);

        $response = $this->patchJson('/api/v1/cart', [
            'coupon_code' => 'SAVE50',
            'shipping_address_id' => $address->id,
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'discount_total' => '50.00',
                'grand_total' => '950.00',
            ]);
    }

    private function createCustomer(): User
    {
        $guard = config('permission.defaults.guard', 'web');
        $role = Role::firstOrCreate(['name' => 'customer', 'guard_name' => $guard]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
