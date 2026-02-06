<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    public function track(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->hasRole('customer') && ! $user->hasAnyRole(['admin', 'staff'])) {
            if ($order->user_id !== $user->id) {
                abort(404);
            }
        }

        $orderedAt = $order->placed_at ?? $order->created_at;
        $paidAt = $order->payment_status === 'paid' ? $order->placed_at : null;

        return ApiResponse::message('Order tracking status.', 200, [
            'order_uuid' => $order->uuid,
            'order_number' => $order->order_number,
            'current_status' => $order->status,
            'timeline' => [
                [
                    'status' => 'ordered',
                    'occurred_at' => $orderedAt,
                ],
                [
                    'status' => 'paid',
                    'occurred_at' => $paidAt,
                ],
                [
                    'status' => 'shipped',
                    'occurred_at' => $order->shipped_at,
                ],
                [
                    'status' => 'delivered',
                    'occurred_at' => $order->delivered_at,
                ],
                [
                    'status' => 'cancelled',
                    'occurred_at' => $order->cancelled_at,
                ],
            ],
        ]);
    }
}
