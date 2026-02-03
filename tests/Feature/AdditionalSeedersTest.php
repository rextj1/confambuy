<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\MarketingSeeder;
use Database\Seeders\ReviewSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdditionalSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_marketing_and_review_seeders_run_successfully(): void
    {
        Product::factory()->count(5)->create();

        $this->seed(UserSeeder::class);
        $this->seed(MarketingSeeder::class);
        $this->seed(ReviewSeeder::class);

        $this->assertCount(20, Address::all());
        $this->assertNotNull(Coupon::where('code', 'WELCOME10')->first());
        $this->assertNotNull(Coupon::where('code', 'SAVE50')->first());
        $this->assertNotNull(Promotion::where('slug', 'summer-sale')->first());
        $this->assertCount(50, Review::all());
        $this->assertGreaterThanOrEqual(20, User::count());
    }
}
