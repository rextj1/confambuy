<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PricingQuoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_customer_can_get_pricing_quote_without_coupon(): void
    {
        config([
            'pricing.tax_zones' => [
                'Nigeria' => [
                    'default' => 0.1,
                    'states' => [],
                ],
                'default' => 0.0,
            ],
            'pricing.shipping.zones' => [
                [
                    'name' => 'Nigeria',
                    'countries' => ['Nigeria'],
                    'states' => [],
                    'carriers' => [
                        'standard' => [
                            'label' => 'Standard',
                            'rates' => [
                                ['max_weight' => 10, 'price' => 500.00],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $user = $this->createCustomer();
        $sku = ProductSku::factory()->create(['price' => 1000.00]);
        $address = Address::factory()->create([
            'user_id' => $user->id,
            'country' => 'Nigeria',
            'state' => 'Lagos',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/pricing/quote', [
            'items' => [
                ['sku_id' => $sku->id, 'quantity' => 2],
            ],
            'shipping_address_id' => $address->id,
            'shipping_method' => 'standard',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Pricing quote',
                'meta' => [
                    'currency' => 'NGN',
                    'subtotal' => '2000.00',
                    'discount' => '0.00',
                    'shipping' => '500.00',
                    'tax' => '200.00',
                    'total' => '2700.00',
                ],
            ]);
    }

    public function test_coupon_percentage_is_applied_with_cap(): void
    {
        config([
            'pricing.tax_zones' => ['default' => 0.0],
            'pricing.shipping.zones' => [],
        ]);

        $user = $this->createCustomer();
        $sku = ProductSku::factory()->create(['price' => 1000.00]);

        Coupon::factory()->create([
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 10,
            'max_discount' => 50.00,
            'min_spend' => 0,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/pricing/quote', [
            'items' => [
                ['sku_id' => $sku->id, 'quantity' => 1],
            ],
            'coupon_code' => 'SAVE10',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'subtotal' => '1000.00',
                'discount' => '50.00',
                'total' => '950.00',
            ]);
    }

    public function test_coupon_product_constraints_are_enforced(): void
    {
        config([
            'pricing.tax_zones' => ['default' => 0.0],
            'pricing.shipping.zones' => [],
        ]);

        $user = $this->createCustomer();
        $category = Category::factory()->create();
        $product = Product::factory()->create();
        $product->categories()->sync([$category->id]);
        $sku = ProductSku::factory()->create(['product_id' => $product->id, 'price' => 500.00]);

        $coupon = Coupon::factory()->create([
            'code' => 'CATONLY',
            'type' => 'fixed_amount',
            'value' => 100.00,
            'min_spend' => 0,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);
        $coupon->categories()->sync([$category->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/pricing/quote', [
            'items' => [
                ['sku_id' => $sku->id, 'quantity' => 1],
            ],
            'coupon_code' => 'CATONLY',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'discount' => '100.00',
                'total' => '400.00',
            ]);
    }

    public function test_coupon_usage_limit_is_enforced(): void
    {
        config([
            'pricing.tax_zones' => ['default' => 0.0],
            'pricing.shipping.zones' => [],
        ]);

        $user = $this->createCustomer();
        $sku = ProductSku::factory()->create(['price' => 1000.00]);

        $coupon = Coupon::factory()->create([
            'code' => 'LIMIT1',
            'type' => 'fixed_amount',
            'value' => 100.00,
            'usage_limit' => 1,
            'min_spend' => 0,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        CouponUsage::factory()->create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'quantity' => 1,
            'used_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/pricing/quote', [
            'items' => [
                ['sku_id' => $sku->id, 'quantity' => 1],
            ],
            'coupon_code' => 'LIMIT1',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'discount' => '0.00',
                'total' => '1000.00',
            ]);
    }

    public function test_coupon_limit_per_user_is_enforced(): void
    {
        config([
            'pricing.tax_zones' => ['default' => 0.0],
            'pricing.shipping.zones' => [],
        ]);

        $user = $this->createCustomer();
        $sku = ProductSku::factory()->create(['price' => 500.00]);

        $coupon = Coupon::factory()->create([
            'code' => 'USER1',
            'type' => 'fixed_amount',
            'value' => 50.00,
            'limit_per_user' => 1,
            'min_spend' => 0,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        CouponUsage::factory()->create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'quantity' => 1,
            'used_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/pricing/quote', [
            'items' => [
                ['sku_id' => $sku->id, 'quantity' => 1],
            ],
            'coupon_code' => 'USER1',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'discount' => '0.00',
                'total' => '500.00',
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
