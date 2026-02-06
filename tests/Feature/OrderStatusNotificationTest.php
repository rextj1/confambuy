<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_status_updates_notify_user_and_staff(): void
    {
        Notification::fake();

        $guard = config('permission.defaults.guard', 'web');
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => $guard]);

        $customer = User::factory()->create();
        $admin = User::factory()->create();
        $staff = User::factory()->create();

        $admin->assignRole($adminRole);
        $staff->assignRole($staffRole);

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
        ]);

        $order->update([
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);

        Notification::assertSentTo($customer, OrderStatusUpdated::class, function (OrderStatusUpdated $notification): bool {
            return $notification->status === 'shipped';
        });

        Notification::assertSentTo($admin, OrderStatusUpdated::class);
        Notification::assertSentTo($staff, OrderStatusUpdated::class);
    }

    public function test_non_status_updates_do_not_notify(): void
    {
        Notification::fake();

        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
        ]);

        $order->update([
            'customer_note' => 'Leave at the door.',
        ]);

        Notification::assertNothingSent();
    }
}
