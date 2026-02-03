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

        if ($users->isEmpty() || $products->isEmpty()) {
            return;
        }

        Review::factory()
            ->count(50)
            ->state(fn () => [
                'user_id' => $users->random()->id,
                'product_id' => $products->random()->id,
            ])
            ->create();
    }
}
