<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_customer_can_place_order_from_cart(): void
    {
        config([
            'pricing.tax_zones' => ['default' => 0.0],
            'pricing.shipping.zones' => [],
        ]);

        $user = $this->createCustomer();
        $sku = ProductSku::factory()->create(['price' => 300.00]);
        Inventory::factory()->create([
            'product_sku_id' => $sku->id,
            'quantity' => 10,
            'reserved' => 0,
            'allow_backorder' => false,
        ]);

        $address = Address::factory()->create(['user_id' => $user->id]);

        $cart = Cart::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $cart->items()->create([
            'product_id' => $sku->product_id,
            'product_sku_id' => $sku->id,
            'name' => $sku->product?->name ?? 'Item',
            'quantity' => 2,
            'unit_price' => 300.00,
            'total' => 600.00,
            'sku_snapshot' => ['sku' => $sku->sku],
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'shipping_method' => 'standard',
            'payment_method' => 'manual',
        ], [
            'Idempotency-Key' => 'checkout-1',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $this->assertDatabaseHas('inventory_reservations', [
            'product_sku_id' => $sku->id,
            'status' => 'consumed',
        ]);
    }

    public function test_coupon_usage_is_recorded_on_checkout(): void
    {
        config([
            'pricing.tax_zones' => ['default' => 0.0],
            'pricing.shipping.zones' => [],
        ]);

        $user = $this->createCustomer();
        $sku = ProductSku::factory()->create(['price' => 200.00]);
        Inventory::factory()->create([
            'product_sku_id' => $sku->id,
            'quantity' => 10,
            'reserved' => 0,
            'allow_backorder' => false,
        ]);

        $address = Address::factory()->create(['user_id' => $user->id]);

        $coupon = Coupon::factory()->create([
            'code' => 'SAVE10',
            'type' => 'fixed_amount',
            'value' => 10.00,
            'min_spend' => 0,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
        ]);

        $cart->items()->create([
            'product_id' => $sku->product_id,
            'product_sku_id' => $sku->id,
            'name' => $sku->product?->name ?? 'Item',
            'quantity' => 1,
            'unit_price' => 200.00,
            'total' => 200.00,
            'sku_snapshot' => ['sku' => $sku->sku],
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'shipping_method' => 'standard',
            'payment_method' => 'manual',
        ], [
            'Idempotency-Key' => 'checkout-2',
        ]);

        $response->assertStatus(201);

        $order = Order::query()->where('user_id', $user->id)->latest()->first();

        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order?->id,
        ]);
    }

    public function test_checkout_is_idempotent_with_key(): void
    {
        config([
            'pricing.tax_zones' => ['default' => 0.0],
            'pricing.shipping.zones' => [],
        ]);

        $user = $this->createCustomer();
        $sku = ProductSku::factory()->create(['price' => 300.00]);
        Inventory::factory()->create([
            'product_sku_id' => $sku->id,
            'quantity' => 10,
            'reserved' => 0,
            'allow_backorder' => false,
        ]);

        $address = Address::factory()->create(['user_id' => $user->id]);

        $cart = Cart::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $cart->items()->create([
            'product_id' => $sku->product_id,
            'product_sku_id' => $sku->id,
            'name' => $sku->product?->name ?? 'Item',
            'quantity' => 1,
            'unit_price' => 300.00,
            'total' => 300.00,
            'sku_snapshot' => ['sku' => $sku->sku],
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'shipping_method' => 'standard',
            'payment_method' => 'manual',
        ];

        $this->postJson('/api/v1/checkout', $payload, [
            'Idempotency-Key' => 'idem-1',
        ])->assertStatus(201);

        $this->postJson('/api/v1/checkout', $payload, [
            'Idempotency-Key' => 'idem-1',
        ])->assertStatus(409);

        $this->assertDatabaseCount('orders', 1);
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
