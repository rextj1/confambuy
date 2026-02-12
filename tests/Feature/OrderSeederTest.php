<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use Database\Seeders\OrderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_are_seeded_correctly()
    {
        // Arrange: Create prerequisites (User with Address, Product)
        $user = User::factory()->create();

        Address::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'line_1' => '123 Test St',
            'city' => 'Test City',
            'state' => 'Test State',
            'postal_code' => '12345',
            'country' => 'Test Country',
            'phone' => '1234567890',
            'default_shipping' => true,
            'default_billing' => true,
        ]);

        $products = Product::factory()->count(3)->create();

        $products->each(function (Product $product): void {
            ProductSku::factory()->create(['product_id' => $product->id]);
        });

        // Act: Run the seeder
        $this->seed(OrderSeeder::class);

        // Assert: Verify Orders were created
        $orders = Order::all();
        $this->assertNotEmpty($orders, 'No orders were seeded.');

        foreach ($orders as $order) {
            // Verify User association
            $this->assertEquals($user->id, $order->user_id);

            // Verify Order Items
            $this->assertNotEmpty($order->items, "Order {$order->id} has no items.");
            $expectedTotal = (float) $order->items->sum('total')
                - (float) $order->discount_total
                + (float) $order->tax_total
                + (float) $order->shipping_total;

            $this->assertEqualsWithDelta(
                $expectedTotal,
                (float) $order->grand_total,
                0.01,
                "Order {$order->id} total mismatch."
            );

            // Verify Payments for paid orders
            if ($order->payment_status === 'paid') {
                $this->assertNotEmpty($order->payments, "Paid order {$order->id} has no payment record.");
                $this->assertEqualsWithDelta(
                    (float) $order->grand_total,
                    (float) $order->payments->first()->amount,
                    0.01
                );
            }

            // Verify Shipments for completed orders
            if ($order->status === 'completed') {
                $this->assertNotEmpty($order->shipments, "Completed order {$order->id} has no shipment record.");
            }
        }
    }
}
