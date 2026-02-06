<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentReceived;
use App\Notifications\PaymentReceivedAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PaystackPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        config(['services.paystack.secret_key' => 'test_secret']);
    }

    public function test_customer_can_initialize_paystack_payment(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://paystack.test/auth',
                    'access_code' => 'ACCESS_CODE',
                ],
            ], 200),
        ]);

        $user = $this->createCustomer();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'unpaid',
            'grand_total' => 1000.00,
            'currency' => 'NGN',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/payments/paystack/initialize', [
            'order_id' => $order->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'meta' => ['reference', 'authorization_url', 'access_code'],
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway' => 'paystack',
            'status' => 'initialized',
        ]);
    }

    public function test_customer_can_verify_paystack_payment(): void
    {
        Notification::fake();

        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'amount' => 100000,
                    'fees' => 1500,
                ],
            ], 200),
        ]);

        $user = $this->createCustomer();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'unpaid',
            'grand_total' => 1000.00,
            'currency' => 'NGN',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'paystack',
            'gateway_id' => 'REF-123',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'status' => 'initialized',
            'captured' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/payments/paystack/verify/REF-123');

        $response->assertOk()
            ->assertJsonFragment(['status' => 'paid']);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
            'captured' => true,
        ]);

        Notification::assertSentTo($user, PaymentReceived::class);
    }

    public function test_initialize_is_idempotent_with_key(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://paystack.test/auth',
                    'access_code' => 'ACCESS_CODE',
                ],
            ], 200),
        ]);

        $user = $this->createCustomer();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'unpaid',
            'grand_total' => 1000.00,
            'currency' => 'NGN',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/payments/paystack/initialize', [
            'order_id' => $order->id,
        ], [
            'Idempotency-Key' => 'idem-123',
        ])->assertOk();

        $this->postJson('/api/v1/payments/paystack/initialize', [
            'order_id' => $order->id,
        ], [
            'Idempotency-Key' => 'idem-123',
        ])->assertOk();

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $payload = json_encode([
            'event' => 'charge.success',
            'data' => ['reference' => 'REF-123'],
        ]);

        $this->postJson('/api/v1/payments/webhook', [], [
            'X-Paystack-Signature' => 'bad',
        ])->assertStatus(401);
    }

    public function test_webhook_dispatches_verification_job_once(): void
    {
        Queue::fake();

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => ['reference' => 'REF-123', 'id' => 'EVT-1'],
        ]);

        $signature = hash_hmac('sha512', $payload, 'test_secret');

        $this->postJson('/api/v1/payments/webhook', json_decode($payload, true), [
            'X-Paystack-Signature' => $signature,
        ])->assertOk();

        $this->postJson('/api/v1/payments/webhook', json_decode($payload, true), [
            'X-Paystack-Signature' => $signature,
        ])->assertOk();

        Queue::assertPushed(\App\Jobs\VerifyPaystackPayment::class, 1);
    }

    public function test_webhook_verification_sends_payment_received_notification(): void
    {
        Notification::fake();

        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'amount' => 100000,
                    'fees' => 1500,
                ],
            ], 200),
        ]);

        $user = $this->createCustomer();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'unpaid',
            'grand_total' => 1000.00,
            'currency' => 'NGN',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'paystack',
            'gateway_id' => 'REF-123',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'status' => 'initialized',
            'captured' => false,
        ]);

        $guard = config('permission.defaults.guard', 'web');
        $admin = User::factory()->create();
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
        $admin->assignRole($adminRole);

        $staff = User::factory()->create();
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => $guard]);
        $staff->assignRole($staffRole);

        (new \App\Jobs\VerifyPaystackPayment('REF-123'))->handle();

        Notification::assertSentTo($user, PaymentReceived::class, function (PaymentReceived $notification, array $channels) use ($order, $payment): bool {
            return in_array('mail', $channels, true)
                && in_array('database', $channels, true)
                && $notification->order->is($order)
                && $notification->payment->is($payment);
        });

        Notification::assertSentTo($admin, PaymentReceivedAdmin::class, function (PaymentReceivedAdmin $notification, array $channels) use ($order, $payment): bool {
            return in_array('mail', $channels, true)
                && in_array('database', $channels, true)
                && $notification->order->is($order)
                && $notification->payment->is($payment);
        });

        Notification::assertSentTo($staff, PaymentReceivedAdmin::class, function (PaymentReceivedAdmin $notification, array $channels) use ($order, $payment): bool {
            return in_array('mail', $channels, true)
                && in_array('database', $channels, true)
                && $notification->order->is($order)
                && $notification->payment->is($payment);
        });
    }

    private function createCustomer(): User
    {
        $guard = config('permission.defaults.guard', 'web');
        $role = Role::firstOrCreate(['name' => 'customer', 'guard_name' => $guard]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
