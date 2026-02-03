<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        $address = [
            'name' => fake()->name(),
            'line_1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => 'Nigeria',
            'phone' => fake()->phoneNumber(),
        ];

        return [
            'user_id' => User::factory(),
            'order_number' => fake()->unique()->bothify('ORD-####-####'),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'cancelled']),
            'subtotal' => $subtotal = fake()->randomFloat(2, 5000, 50000),
            'shipping_total' => $shipping = fake()->randomFloat(2, 1000, 5000),
            'tax_total' => $tax = $subtotal * 0.075,
            'discount_total' => 0,
            'grand_total' => $subtotal + $shipping + $tax,
            'currency' => 'NGN',
            'payment_status' => fake()->randomElement(['paid', 'unpaid']),
            'payment_method' => 'card',
            'shipping_address_snapshot' => $address,
            'billing_address_snapshot' => $address,
            'placed_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}