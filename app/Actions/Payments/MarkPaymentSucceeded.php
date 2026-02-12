<?php

namespace App\Actions\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentReceived;
use App\Notifications\PaymentReceivedAdmin;
use Spatie\Permission\Models\Role;

class MarkPaymentSucceeded
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Payment $payment, array $payload): void
    {
        if ($payment->status === 'succeeded') {
            return;
        }

        /** @var Order|null $order */
        $order = $payment->order;

        if (! $order) {
            return;
        }

        $wasPaid = $order->payment_status === 'paid';

        $payment->update([
            'status' => 'succeeded',
            'captured' => true,
            'fee' => isset($payload['data']['fees']) ? ((float) $payload['data']['fees'] / 100) : 0,
            'payload' => $payload,
        ]);

        $order->update([
            'payment_status' => 'paid',
            'placed_at' => $order->placed_at ?? now(),
        ]);

        if ($wasPaid || ! $order->user) {
            return;
        }

        $order->user->notify(new PaymentReceived($order, $payment));

        $guard = config('permission.defaults.guard', 'web');

        foreach (['admin', 'staff'] as $roleName) {
            if (! Role::query()->where('name', $roleName)->where('guard_name', $guard)->exists()) {
                continue;
            }

            User::role($roleName)->get()->each(function (User $recipient) use ($order, $payment): void {
                $recipient->notify(new PaymentReceivedAdmin($order, $payment));
            });
        }
    }
}
