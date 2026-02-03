<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelSchemaAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_persist_expected_schema_fields(): void
    {
        $promotion = Promotion::create([
            'name' => 'Weekend Sale',
            'slug' => 'weekend-sale',
            'type' => 'percentage',
            'value' => 10,
            'banner_url' => 'https://example.test/banner.jpg',
            'is_active' => true,
        ]);

        $coupon = Coupon::create([
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
            'min_spend' => 500,
            'max_discount' => 1000,
            'is_active' => true,
            'is_automatic' => false,
        ]);

        $order = Order::factory()->create();

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'carrier' => 'DHL',
            'service' => 'Express',
            'tracking_number' => 'TRACK123',
            'tracking_url' => 'https://example.test/track/TRACK123',
            'cost' => 500,
            'status' => 'pending',
            'label' => ['file' => 'label.pdf'],
            'shipped_at' => now(),
        ]);

        $this->assertSame('https://example.test/banner.jpg', $promotion->banner_url);
        $this->assertSame('500.00', $coupon->min_spend);
        $this->assertSame('1000.00', $coupon->max_discount);
        $this->assertSame(['file' => 'label.pdf'], $shipment->label);
        $this->assertNotNull($shipment->shipped_at);
    }
}
