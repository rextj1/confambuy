<?php

namespace App\Actions\Pricing;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductSku;

class CalculateSellingPrice
{
    /**
     * @return array{
     *     price:string,
     *     compare_at_price:?string,
     *     selling_price:string,
     *     discount_amount:string,
     *     discount_percent:int
     * }
     */
    public function forProduct(Product $product): array
    {
        $price = (float) $product->price;
        $compareAt = $product->compare_at_price !== null ? (float) $product->compare_at_price : null;

        return $this->buildBreakdown($price, $compareAt);
    }

    /**
     * @return array{
     *     price:string,
     *     compare_at_price:?string,
     *     selling_price:string,
     *     discount_amount:string,
     *     discount_percent:int
     * }
     */
    public function forSku(ProductSku $sku, ?Coupon $coupon = null): array
    {
        $price = (float) $sku->price;
        $compareAt = null;

        $breakdown = $this->buildBreakdown($price, $compareAt);

        if ($coupon) {
            $sellingPrice = $this->applyCoupon($price, $coupon);
            $discountAmount = max(0, $price - $sellingPrice);

            $breakdown['selling_price'] = $this->format($sellingPrice);
            $breakdown['discount_amount'] = $this->format($discountAmount);
            $breakdown['discount_percent'] = $this->percent($price, $discountAmount);
        }

        return $breakdown;
    }

    /**
     * @return array{
     *     price:string,
     *     compare_at_price:?string,
     *     selling_price:string,
     *     discount_amount:string,
     *     discount_percent:int
     * }
     */
    private function buildBreakdown(float $price, ?float $compareAt): array
    {
        $sellingPrice = $price;
        $discountAmount = 0.0;

        if ($compareAt !== null && $compareAt > $price) {
            $discountAmount = $compareAt - $price;
            $sellingPrice = $price;
        }

        return [
            'price' => $this->format($price),
            'compare_at_price' => $compareAt !== null ? $this->format($compareAt) : null,
            'selling_price' => $this->format($sellingPrice),
            'discount_amount' => $this->format($discountAmount),
            'discount_percent' => $this->percent($compareAt ?? $price, $discountAmount),
        ];
    }

    private function applyCoupon(float $price, Coupon $coupon): float
    {
        if (! $coupon->is_active) {
            return $price;
        }

        $now = now();

        if ($coupon->starts_at && $coupon->starts_at->isAfter($now)) {
            return $price;
        }

        if ($coupon->expires_at && $coupon->expires_at->isBefore($now)) {
            return $price;
        }

        if ($coupon->min_spend && $price < (float) $coupon->min_spend) {
            return $price;
        }

        $discount = 0.0;

        if ($coupon->type === 'percentage') {
            $discount = $price * ((float) $coupon->value / 100);
        } elseif ($coupon->type === 'fixed_amount') {
            $discount = (float) $coupon->value;
        }

        if ($coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return max(0.0, $price - $discount);
    }

    private function percent(float $base, float $discount): int
    {
        if ($base <= 0 || $discount <= 0) {
            return 0;
        }

        return (int) round(($discount / $base) * 100);
    }

    private function format(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
