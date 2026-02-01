<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $products = Product::all();

        if ($users->isEmpty() || $products->isEmpty()) return;

        // Create 50 random reviews
        for ($i = 0; $i < 50; $i++) {
            Review::create([
                'user_id' => $users->random()->id,
                'product_id' => $products->random()->id,
                'rating' => rand(1, 5),
                'title' => fake()->sentence(),
                'body' => fake()->paragraph(),
                'approved' => true,
                'approved_at' => now(),
            ]);
        }
    }
}