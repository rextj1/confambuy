<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Promotion;
use Illuminate\Database\Seeder;

class MarketingSeeder extends Seeder
{
    public function run(): void
    {
        // Coupons
        Coupon::query()->updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'type' => 'percentage',
                'value' => 10,
                'usage_limit' => 1000,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
            ]
        );

        Coupon::query()->updateOrCreate(
            ['code' => 'SAVE50'],
            [
                'type' => 'fixed_amount',
                'value' => 50,
                'usage_limit' => 100,
                'starts_at' => now(),
                'expires_at' => now()->addMonth(),
            ]
        );

        // Promotions
        Promotion::query()->updateOrCreate(
            ['slug' => 'summer-sale'],
            [
                'name' => 'Summer Sale',
                'type' => 'percentage',
                'value' => 15,
                'is_active' => true,
                'starts_at' => now()->subDays(5),
                'ends_at' => now()->addDays(10),
            ]
        );
    }
}
