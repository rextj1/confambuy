<?php

namespace App\Actions\Pricing;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\ProductSku;
use Illuminate\Support\Collection;

class CalculateOrderTotals
{
    /**
     * @param  \Illuminate\Support\Collection<int, array{sku: ProductSku, quantity: int}>  $items
     * @return array{
     *     currency:string,
     *     subtotal:string,
     *     discount:string,
     *     shipping:string,
     *     tax:string,
     *     total:string,
     *     tax_breakdown:array<string, mixed>,
     *     shipping_breakdown:array<string, mixed>
     * }
     */
    public function calculate(
        Collection $items,
        ?Coupon $coupon = null,
        ?Address $shippingAddress = null,
        ?string $shippingMethod = null,
        ?int $userId = null
    ): array {
        $round = app(RoundMoney::class);
        $taxCalculator = app(CalculateTax::class);
        $shippingCalculator = app(CalculateShipping::class);

        $subtotal = 0.0;
        $weight = 0.0;

        foreach ($items as $item) {
            $price = (float) $item['sku']->price;
            $subtotal += $price * $item['quantity'];

            $skuWeight = (float) ($item['sku']->weight ?? 0);
            $weight += $skuWeight * $item['quantity'];
        }

        $discount = $coupon ? $this->calculateDiscount($subtotal, $coupon, $items, $userId) : 0.0;
        $taxable = max(0.0, $subtotal - $discount);

        $taxBreakdown = $taxCalculator->forAddress($taxable, $shippingAddress);
        $tax = $taxBreakdown['amount'];

        $shippingBreakdown = [
            'amount' => 0.0,
            'carrier' => $shippingMethod ?? (string) config('pricing.shipping.default_carrier', 'standard'),
            'label' => null,
            'zone' => null,
        ];
        $shipping = 0.0;

        if ($subtotal > 0) {
            $shippingBreakdown = $shippingCalculator->forAddress($weight, $shippingAddress, $shippingMethod);
            $shipping = $shippingBreakdown['amount'];
        }

        $total = $taxable + $tax + $shipping;

        return [
            'currency' => (string) config('pricing.currency', 'NGN'),
            'subtotal' => $this->format($round->handle($subtotal)),
            'discount' => $this->format($round->handle($discount)),
            'shipping' => $this->format($round->handle($shipping)),
            'tax' => $this->format($round->handle($tax)),
            'total' => $this->format($round->handle($total)),
            'tax_breakdown' => $taxBreakdown,
            'shipping_breakdown' => $shippingBreakdown,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{sku: ProductSku, quantity: int}>  $items
     */
    private function calculateDiscount(float $subtotal, Coupon $coupon, Collection $items, ?int $userId): float
    {
        if (! $coupon->is_active) {
            return 0.0;
        }

        $now = now();

        if ($coupon->starts_at && $coupon->starts_at->isAfter($now)) {
            return 0.0;
        }

        if ($coupon->expires_at && $coupon->expires_at->isBefore($now)) {
            return 0.0;
        }

        if ($coupon->min_spend && $subtotal < (float) $coupon->min_spend) {
            return 0.0;
        }

        if (! $this->couponWithinLimits($coupon, $userId)) {
            return 0.0;
        }

        if (! $this->couponAppliesToItems($coupon, $items)) {
            return 0.0;
        }

        $discount = 0.0;

        if ($coupon->type === 'percentage') {
            $discount = $subtotal * ((float) $coupon->value / 100);
        } elseif ($coupon->type === 'fixed_amount') {
            $discount = (float) $coupon->value;
        }

        if ($coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return max(0.0, min($subtotal, $discount));
    }

    private function couponWithinLimits(Coupon $coupon, ?int $userId): bool
    {
        $totalUsage = CouponUsage::query()
            ->where('coupon_id', $coupon->id)
            ->sum('quantity');

        $totalUsage = max($totalUsage, (int) $coupon->used_count);

        if ($coupon->usage_limit !== null && $totalUsage >= (int) $coupon->usage_limit) {
            return false;
        }

        if ($userId && $coupon->limit_per_user !== null) {
            $userUsage = CouponUsage::query()
                ->where('coupon_id', $coupon->id)
                ->where('user_id', $userId)
                ->sum('quantity');

            if ($userUsage >= (int) $coupon->limit_per_user) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{sku: ProductSku, quantity: int}>  $items
     */
    private function couponAppliesToItems(Coupon $coupon, Collection $items): bool
    {
        $coupon->loadMissing(['products', 'categories']);

        if ($coupon->products->isEmpty() && $coupon->categories->isEmpty()) {
            return true;
        }

        $productIds = $items->map(fn (array $item): int => (int) $item['sku']->product_id)->unique();

        if ($coupon->products->isNotEmpty() && $coupon->products->pluck('id')->intersect($productIds)->isNotEmpty()) {
            return true;
        }

        if ($coupon->categories->isNotEmpty()) {
            $categoryIds = $coupon->categories->pluck('id');

            return ProductSku::query()
                ->whereIn('id', $items->pluck('sku.id'))
                ->whereHas('product.categories', function ($query) use ($categoryIds): void {
                    $query->whereIn('categories.id', $categoryIds);
                })
                ->exists();
        }

        return false;
    }

    private function format(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
