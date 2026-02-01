<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
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
            'full_name' => $user->name,
            'address_line_1' => '123 Test St',
            'city' => 'Test City',
            'state' => 'Test State',
            'postal_code' => '12345',
            'country' => 'Test Country',
            'phone' => '1234567890',
            'is_default' => true,
        ]);

        Product::factory()->count(3)->create();

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
            $this->assertEquals($order->grand_total, $order->items->sum('total_price'), "Order {$order->id} total mismatch.");

            // Verify Payments for paid orders
            if ($order->is_paid) {
                $this->assertNotEmpty($order->payments, "Paid order {$order->id} has no payment record.");
                $this->assertEquals($order->grand_total, $order->payments->first()->amount);
            }

            // Verify Shipments for completed orders
            if ($order->status === 'completed') {
                $this->assertNotEmpty($order->shipments, "Completed order {$order->id} has no shipment record.");
            }
        }
    }
}