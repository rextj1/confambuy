<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('email', '!=', 'admin@confambuy.com')->get();
        $products = Product::with('skus')->get();

        if ($products->isEmpty()) return;

        foreach ($users as $user) {
            // Create 1-3 orders per user
            for ($i = 0; $i < rand(1, 3); $i++) {
                $address = $user->addresses()->first();

                $addressData = $address ? $address->toArray() : [
                    'name' => $user->name,
                    'line_1' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'state' => fake()->state(),
                    'postal_code' => fake()->postcode(),
                    'country' => fake()->country(),
                    'phone' => fake()->phoneNumber(),
                ];
                
                $order = Order::create([
                    'user_id' => $user->id,
                    'status' => fake()->randomElement(['pending', 'processing', 'completed', 'cancelled']),
                    'grand_total' => 0,
                    'payment_status' => fake()->boolean(80) ? 'paid' : 'unpaid',
                    'payment_method' => 'credit_card',
                    'shipping_address_id' => $address?->id,
                    'billing_address_id' => $address?->id,
                    'shipping_address_snapshot' => $addressData,
                    'billing_address_snapshot' => $addressData,
                ]);

                $grandTotal = 0;

                // Add 1-5 items per order
                for ($j = 0; $j < rand(1, 5); $j++) {
                    $product = $products->random();
                    $sku = $product->skus->first(); // Grab first variant
                    $qty = rand(1, 3);
                    $price = $sku ? $sku->price : $product->price;
                    $total = $price * $qty;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_sku_id' => $sku?->id,
                        'name' => $product->name,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'total' => $total,
                    ]);

                    $grandTotal += $total;
                }

                $order->update([
                    'grand_total' => $grandTotal,
                ]);

                // Create Payment
                if ($order->payment_status === 'paid') {
                    Payment::create([
                        'order_id' => $order->id,
                        'gateway' => 'stripe',
                        'amount' => $grandTotal,
                        'currency' => 'USD',
                        'status' => 'succeeded',
                        'captured' => true,
                    ]);
                }

                // Create Shipment if completed
                if ($order->status === 'completed') {
                    Shipment::create([
                        'order_id' => $order->id,
                        'carrier' => 'FedEx',
                        'tracking_number' => 'TRK-' . strtoupper(Str::random(10)),
                        'status' => 'delivered',
                        'cost' => 15.00,
                    ]);
                }
            }
        }
    }
}