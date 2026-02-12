<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Pricing\BuildSkuSnapshot;
use App\Actions\Pricing\CalculateOrderTotals;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\Inventory\InventoryService;
use App\Support\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @group Checkout
 *
 * Convert an active cart into an order.
 *
 * @authenticated
 */
class CheckoutController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:customer');
    }

    public function placeOrder(
        CheckoutRequest $request,
        CalculateOrderTotals $calculator,
        InventoryService $inventoryService,
        BuildSkuSnapshot $snapshot
    ): JsonResponse {
        $user = $request->user();
        $data = $request->validated();
        $idempotencyKey = $data['idempotency_key'];

        $existing = Order::query()
            ->where('user_id', $user->id)
            ->where('idempotency_key', $idempotencyKey)
            ->latest()
            ->first();

        if ($existing) {
            if ($existing->status === 'processing') {
                return ApiResponse::message('Order is already processing for this idempotency key.', 409);
            }

            return ApiResponse::resource(new OrderResource($existing->load('items')), 200);
        }

        $cart = Cart::query()
            ->with(['items.sku.product', 'coupon'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        $shippingAddress = Address::query()
            ->where('id', $data['shipping_address_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $billingAddress = Address::query()
            ->where('id', $data['billing_address_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($cart->items->isEmpty()) {
            return ApiResponse::message('Cart is empty.', 422);
        }

        $lineItems = $cart->items->map(function ($item): array {
            return [
                'sku' => $item->sku,
                'quantity' => $item->quantity,
            ];
        })->filter(fn (array $item): bool => (bool) $item['sku']);

        $totals = $calculator->calculate(
            $lineItems,
            $cart->coupon,
            $shippingAddress,
            $data['shipping_method'],
            $user->id
        );

        try {
            DB::beginTransaction();

            $reservations = $inventoryService->reserve($lineItems, $cart->id.'-'.$user->id);

            $order = Order::create([
                'user_id' => $user->id,
                'idempotency_key' => $idempotencyKey,
                'status' => 'processing',
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount'],
                'tax_total' => $totals['tax'],
                'shipping_total' => $totals['shipping'],
                'grand_total' => $totals['total'],
                'currency' => $totals['currency'],
                'payment_status' => 'unpaid',
                'payment_method' => $data['payment_method'],
                'shipping_address_id' => $shippingAddress->id,
                'billing_address_id' => $billingAddress->id,
                'shipping_address_snapshot' => $shippingAddress->toArray(),
                'billing_address_snapshot' => $billingAddress->toArray(),
                'shipping_method' => $data['shipping_method'],
                'tax_breakdown' => $totals['tax_breakdown'],
            ]);

            foreach ($cart->items as $item) {
                if (! $item->sku) {
                    continue;
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_sku_id' => $item->product_sku_id,
                    'sku' => $item->sku->sku,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'unit_cost' => $item->sku->cost,
                    'total' => $item->total,
                    'sku_snapshot' => $snapshot->fromSku($item->sku),
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'gateway' => $data['payment_method'] === 'paystack' ? 'paystack' : 'manual',
                'payment_method' => $data['payment_method'],
                'amount' => $order->grand_total,
                'currency' => $order->currency,
                'status' => 'pending',
                'captured' => false,
            ]);

            if ($cart->coupon_id) {
                CouponUsage::create([
                    'coupon_id' => $cart->coupon_id,
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'cart_id' => $cart->id,
                    'quantity' => 1,
                    'used_at' => now(),
                ]);
            }

            foreach ($reservations as $reservation) {
                $reservation->update(['order_id' => $order->id]);
                $inventoryService->consume($reservation);
            }

            $cart->update([
                'status' => 'converted',
            ]);

            $cart->items()->delete();

            DB::commit();

            return ApiResponse::resource(new OrderResource($order->load('items')), 201);
        } catch (QueryException $exception) {
            DB::rollBack();

            $existing = Order::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->latest()
                ->first();

            if ($existing) {
                if ($existing->status === 'processing') {
                    return ApiResponse::message('Order is already processing for this idempotency key.', 409);
                }

                return ApiResponse::resource(new OrderResource($existing->load('items')), 200);
            }

            throw $exception;
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }
    }
}
