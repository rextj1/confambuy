<?php

namespace App\Services\Payments\Webhooks;

use App\Jobs\VerifyPaystackPayment;
use App\Models\PaystackWebhookEvent;

class PaystackWebhookHandler
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $signature): void
    {
        $event = (string) ($payload['event'] ?? '');
        $reference = (string) ($payload['data']['reference'] ?? '');
        $eventId = (string) ($payload['data']['id'] ?? '');
        $payloadHash = hash('sha256', json_encode($payload));

        if ($eventId && PaystackWebhookEvent::query()->where('event_id', $eventId)->exists()) {
            return;
        }

        if (PaystackWebhookEvent::query()->where('payload_hash', $payloadHash)->exists()) {
            return;
        }

        PaystackWebhookEvent::create([
            'event' => $event ?: null,
            'reference' => $reference ?: null,
            'event_id' => $eventId ?: null,
            'signature' => $signature,
            'payload_hash' => $payloadHash,
            'payload' => $payload,
        ]);

        if ($event === 'charge.success' && $reference) {
            VerifyPaystackPayment::dispatch($reference);
        }
    }
}
