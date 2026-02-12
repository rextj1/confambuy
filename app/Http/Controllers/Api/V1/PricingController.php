<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Pricing\CalculateOrderTotals;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PricingQuoteRequest;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\ProductSku;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @group Pricing
 *
 * Calculate cart pricing quotes before checkout.
 *
 * @authenticated
 */
class PricingController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:customer');
    }

    public function quote(PricingQuoteRequest $request, CalculateOrderTotals $calculator): JsonResponse
    {
        $data = $request->validated();
        $items = collect($data['items']);
        $skuIds = $items->pluck('sku_id')->all();

        $skus = ProductSku::query()
            ->with('product.categories')
            ->whereIn('id', $skuIds)
            ->get()
            ->keyBy('id');

        $lineItems = $items->map(function (array $item) use ($skus): array {
            $sku = $skus->get($item['sku_id']);

            return [
                'sku' => $sku,
                'quantity' => $item['quantity'],
            ];
        });

        $shippingAddress = null;

        if (! empty($data['shipping_address_id'])) {
            $shippingAddress = Address::query()
                ->where('id', $data['shipping_address_id'])
                ->where('user_id', $request->user()->id)
                ->first();
        }

        $coupon = null;

        if (! empty($data['coupon_code'])) {
            $coupon = Coupon::query()
                ->where('code', $data['coupon_code'])
                ->first();
        }

        $totals = $calculator->calculate(
            $lineItems,
            $coupon,
            $shippingAddress,
            $data['shipping_method'] ?? null,
            $request->user()->id
        );

        return ApiResponse::message('Pricing quote', 200, $totals);
    }
}
