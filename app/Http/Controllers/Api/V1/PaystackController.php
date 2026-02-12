<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Payments\MarkPaymentSucceeded;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PaystackInitializeRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\PaystackClient;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @group Payments
 *
 * Initialize and verify Paystack payments.
 *
 * @authenticated
 */
class PaystackController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:customer')->only(['initialize', 'verify']);
    }

    public function initialize(PaystackInitializeRequest $request, PaystackClient $client): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $idempotencyKey = (string) $request->header('Idempotency-Key');

        $order = Order::query()
            ->where('id', $data['order_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($order->payment_status === 'paid') {
            return ApiResponse::message('Order already paid.', 422);
        }

        if ($idempotencyKey) {
            $existing = Payment::query()
                ->where('order_id', $order->id)
                ->where('gateway', 'paystack')
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing && $existing->payload) {
                return ApiResponse::message('Paystack initialized', 200, [
                    'reference' => $existing->gateway_id,
                    'authorization_url' => data_get($existing->payload, 'data.authorization_url'),
                    'access_code' => data_get($existing->payload, 'data.access_code'),
                ]);
            }
        } else {
            $existing = Payment::query()
                ->where('order_id', $order->id)
                ->where('gateway', 'paystack')
                ->whereIn('status', ['initialized', 'pending'])
                ->latest()
                ->first();

            if ($existing && $existing->payload) {
                return ApiResponse::message('Paystack initialized', 200, [
                    'reference' => $existing->gateway_id,
                    'authorization_url' => data_get($existing->payload, 'data.authorization_url'),
                    'access_code' => data_get($existing->payload, 'data.access_code'),
                ]);
            }
        }

        $reference = (string) Str::uuid();

        $payload = [
            'email' => $user->email,
            'amount' => (int) round(((float) $order->grand_total) * 100),
            'currency' => (string) config('pricing.currency', 'NGN'),
            'reference' => $reference,
            'callback_url' => $data['callback_url'] ?? null,
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $user->id,
            ],
        ];

        $payload = array_filter($payload, fn ($value): bool => $value !== null);

        $response = $client->initialize($payload);

        if (! $response->successful()) {
            return ApiResponse::message('Unable to initialize payment.', 422, [
                'gateway' => $response->json(),
            ]);
        }

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'gateway' => 'paystack',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'status' => 'initialized',
            'payment_method' => 'paystack',
            'captured' => false,
            'idempotency_key' => $idempotencyKey ?: null,
        ]);

        $payment->update([
            'gateway_id' => $reference,
            'payload' => $response->json(),
        ]);

        return ApiResponse::message('Paystack initialized', 200, [
            'reference' => $reference,
            'authorization_url' => data_get($response->json(), 'data.authorization_url'),
            'access_code' => data_get($response->json(), 'data.access_code'),
        ]);
    }

    public function verify(Request $request, string $reference, PaystackClient $client): JsonResponse
    {
        $payment = Payment::query()
            ->where('gateway', 'paystack')
            ->where('gateway_id', $reference)
            ->firstOrFail();

        $order = $payment->order;

        if ($order->user_id !== $request->user()->id) {
            abort(404);
        }

        $response = $client->verify($reference);

        if (! $response->successful()) {
            return ApiResponse::message('Unable to verify payment.', 422, [
                'gateway' => $response->json(),
            ]);
        }

        return $this->handleVerificationResponse($payment, $order, $response->json());
    }

    public function webhook(Request $request): JsonResponse
    {
        return ApiResponse::message('Use /api/v1/payments/webhook.', 410);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleVerificationResponse(Payment $payment, Order $order, array $payload): JsonResponse
    {
        $data = $payload['data'] ?? [];
        $status = (string) ($data['status'] ?? '');
        $amount = (int) ($data['amount'] ?? 0);

        $expectedAmount = (int) round(((float) $order->grand_total) * 100);

        if ($status === 'success' && $amount === $expectedAmount) {
            app(MarkPaymentSucceeded::class)->handle($payment, $payload);

            return ApiResponse::message('Payment verified', 200, [
                'status' => 'paid',
            ]);
        }

        $payment->update([
            'status' => 'failed',
            'payload' => $payload,
        ]);

        return ApiResponse::message('Payment not successful.', 422, [
            'status' => $status,
        ]);
    }
}
