<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Payments\Webhooks\PaystackWebhookHandler;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Webhooks
 *
 * Public payment webhook ingestion endpoint.
 *
 * @unauthenticated
 */
class WebhookController extends Controller
{
    public function payments(Request $request, PaystackWebhookHandler $handler): JsonResponse
    {
        $signature = (string) $request->header('X-Paystack-Signature');
        $secret = (string) config('services.paystack.secret_key');

        $payload = $request->getContent();
        $computed = hash_hmac('sha512', $payload, $secret);

        if (! hash_equals($computed, $signature)) {
            return ApiResponse::message('Invalid signature.', 401);
        }

        $handler->handle($request->json()->all(), $signature);

        return ApiResponse::message('Webhook received');
    }
}
