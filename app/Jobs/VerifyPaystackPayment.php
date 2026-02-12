<?php

namespace App\Jobs;

use App\Actions\Payments\MarkPaymentSucceeded;
use App\Models\Payment;
use App\Services\Payments\PaystackClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class VerifyPaystackPayment implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $reference) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $payment = Payment::query()
            ->where('gateway', 'paystack')
            ->where('gateway_id', $this->reference)
            ->first();

        if (! $payment) {
            return;
        }

        $client = app(PaystackClient::class);
        $response = $client->verify($this->reference);

        if (! $response->successful()) {
            return;
        }

        $payload = $response->json();
        $data = $payload['data'] ?? [];
        $status = (string) ($data['status'] ?? '');
        $amount = (int) ($data['amount'] ?? 0);
        $expectedAmount = $payment->order
            ? (int) round(((float) $payment->order->grand_total) * 100)
            : null;

        if ($status === 'success' && $expectedAmount !== null && $amount === $expectedAmount) {
            app(MarkPaymentSucceeded::class)->handle($payment, $payload);

            return;
        }

        $payment->update([
            'status' => 'failed',
            'payload' => $payload,
        ]);
    }
}
