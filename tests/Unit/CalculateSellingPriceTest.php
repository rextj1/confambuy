<?php

namespace Tests\Unit;

use App\Actions\Pricing\CalculateSellingPrice;
use App\Models\Product;
use PHPUnit\Framework\TestCase;

class CalculateSellingPriceTest extends TestCase
{
    public function test_returns_price_when_no_compare_at_price(): void
    {
        $product = new Product([
            'price' => 120.00,
            'compare_at_price' => null,
        ]);

        $pricing = (new CalculateSellingPrice)->forProduct($product);

        $this->assertSame('120.00', $pricing['selling_price']);
        $this->assertSame('0.00', $pricing['discount_amount']);
        $this->assertSame(0, $pricing['discount_percent']);
    }

    public function test_calculates_discount_from_compare_at_price(): void
    {
        $product = new Product([
            'price' => 80.00,
            'compare_at_price' => 100.00,
        ]);

        $pricing = (new CalculateSellingPrice)->forProduct($product);

        $this->assertSame('80.00', $pricing['selling_price']);
        $this->assertSame('20.00', $pricing['discount_amount']);
        $this->assertSame(20, $pricing['discount_percent']);
    }
}
