<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Pricing\BuildSkuSnapshot;
use App\Actions\Pricing\CalculateOrderTotals;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AddCartItemRequest;
use App\Http\Requests\Api\V1\UpdateCartItemRequest;
use App\Http\Requests\Api\V1\UpdateCartRequest;
use App\Http\Resources\CartResource;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\ProductSku;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Cart
 *
 * Manage the authenticated customer's cart.
 *
 * @authenticated
 */
class CartController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:customer');
    }

    public function show(Request $request): JsonResponse
    {
        $cart = $this->getActiveCart($request);
        $cart->load('items');

        return ApiResponse::resource(new CartResource($cart));
    }

    public function update(UpdateCartRequest $request, CalculateOrderTotals $calculator): JsonResponse
    {
        $cart = $this->getActiveCart($request);
        $data = $request->validated();

        $cart->shipping_address_id = $this->resolveAddressId($request, $data['shipping_address_id'] ?? null);
        $cart->billing_address_id = $this->resolveAddressId($request, $data['billing_address_id'] ?? null);
        $cart->shipping_method = $data['shipping_method'] ?? $cart->shipping_method;

        if (array_key_exists('coupon_code', $data)) {
            $coupon = $this->resolveCoupon($data['coupon_code']);
            $cart->coupon_id = $coupon?->id;
            $cart->coupon_code = $coupon?->code;
        }

        $cart->save();

        $this->recalculateCart($cart, $calculator);
        $cart->load('items');

        return ApiResponse::resource(new CartResource($cart));
    }

    public function addItem(
        AddCartItemRequest $request,
        BuildSkuSnapshot $snapshot,
        CalculateOrderTotals $calculator
    ): JsonResponse {
        $cart = $this->getActiveCart($request);
        $data = $request->validated();

        $sku = ProductSku::query()
            ->with('product')
            ->findOrFail($data['sku_id']);

        $item = $cart->items()
            ->where('product_sku_id', $sku->id)
            ->first();

        $quantity = (int) $data['quantity'];
        $unitPrice = (float) $sku->price;

        if ($item) {
            $item->quantity += $quantity;
            $item->unit_price = $unitPrice;
            $item->total = $unitPrice * $item->quantity;
            $item->save();
        } else {
            $cart->items()->create([
                'product_id' => $sku->product_id,
                'product_sku_id' => $sku->id,
                'name' => $sku->product?->name ?? $sku->title ?? 'Item',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $unitPrice * $quantity,
                'sku_snapshot' => $snapshot->fromSku($sku),
            ]);
        }

        $this->recalculateCart($cart, $calculator);
        $cart->load('items');

        return ApiResponse::resource(new CartResource($cart));
    }

    public function updateItem(
        UpdateCartItemRequest $request,
        CartItem $cartItem,
        CalculateOrderTotals $calculator
    ): JsonResponse {
        $cart = $this->ensureCartOwnership($request, $cartItem);

        $quantity = (int) $request->validated()['quantity'];
        $cartItem->quantity = $quantity;
        $cartItem->total = (float) $cartItem->unit_price * $quantity;
        $cartItem->save();

        $this->recalculateCart($cart, $calculator);
        $cart->load('items');

        return ApiResponse::resource(new CartResource($cart));
    }

    public function removeItem(Request $request, CartItem $cartItem, CalculateOrderTotals $calculator): JsonResponse
    {
        $cart = $this->ensureCartOwnership($request, $cartItem);

        $cartItem->delete();

        $this->recalculateCart($cart, $calculator);
        $cart->load('items');

        return ApiResponse::resource(new CartResource($cart));
    }

    private function getActiveCart(Request $request): Cart
    {
        $cart = Cart::query()->firstOrCreate(
            ['user_id' => $request->user()->id, 'status' => 'active'],
            [
                'currency' => (string) config('pricing.currency', 'NGN'),
            ]
        );

        $cart->loadMissing('items');

        return $cart;
    }

    private function resolveCoupon(?string $code): ?Coupon
    {
        if (! $code) {
            return null;
        }

        return Coupon::query()->where('code', $code)->first();
    }

    private function resolveAddressId(Request $request, ?int $addressId): ?int
    {
        if (! $addressId) {
            return null;
        }

        $address = Address::query()
            ->where('id', $addressId)
            ->where('user_id', $request->user()->id)
            ->first();

        return $address?->id;
    }

    private function recalculateCart(Cart $cart, CalculateOrderTotals $calculator): void
    {
        $cart->load('items.sku', 'shippingAddress', 'coupon');

        $lineItems = $cart->items->map(function (CartItem $item): array {
            return [
                'sku' => $item->sku,
                'quantity' => $item->quantity,
            ];
        })->filter(fn (array $item): bool => (bool) $item['sku']);

        $totals = $calculator->calculate(
            $lineItems,
            $cart->coupon,
            $cart->shippingAddress,
            $cart->shipping_method,
            $cart->user_id
        );

        $cart->update([
            'currency' => $totals['currency'],
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discount'],
            'tax_total' => $totals['tax'],
            'shipping_total' => $totals['shipping'],
            'grand_total' => $totals['total'],
        ]);
    }

    private function ensureCartOwnership(Request $request, CartItem $cartItem): Cart
    {
        $cart = $cartItem->cart;

        if (! $cart || $cart->user_id !== $request->user()->id) {
            abort(404);
        }

        return $cart;
    }
}
