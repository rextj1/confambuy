<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_track_own_order(): void
    {
        Notification::fake();

        $guard = config('permission.defaults.guard', 'web');
        $role = Role::firstOrCreate(['name' => 'customer', 'guard_name' => $guard]);

        $customer = User::factory()->create();
        $customer->assignRole($role);

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'placed_at' => now(),
        ]);

        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/orders/'.$order->uuid.'/track')
            ->assertOk()
            ->assertJsonFragment([
                'order_uuid' => $order->uuid,
                'current_status' => 'processing',
            ]);
    }

    public function test_customer_cannot_track_someone_else_order(): void
    {
        Notification::fake();

        $guard = config('permission.defaults.guard', 'web');
        $role = Role::firstOrCreate(['name' => 'customer', 'guard_name' => $guard]);

        $customer = User::factory()->create();
        $customer->assignRole($role);

        $other = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $other->id,
        ]);

        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/orders/'.$order->uuid.'/track')
            ->assertStatus(404);
    }

    public function test_admin_can_track_any_order(): void
    {
        Notification::fake();

        $guard = config('permission.defaults.guard', 'web');
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);

        $admin = User::factory()->create();
        $admin->assignRole($role);

        $order = Order::factory()->create();

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/orders/'.$order->uuid.'/track')
            ->assertOk()
            ->assertJsonFragment([
                'order_uuid' => $order->uuid,
            ]);
    }
}
