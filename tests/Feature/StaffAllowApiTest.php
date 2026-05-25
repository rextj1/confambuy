<?php

namespace Tests\Feature;

use App\Models\StaffAllow;
use App\Models\User;
use App\Notifications\StaffRegistrationInvite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StaffAllowApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_admin_can_add_staff_email_to_allowlist(): void
    {
        Notification::fake();
        config(['app.url' => 'https://confambuy.test']);

        $adminRole = Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/staff-allows', [
            'email' => '  STAFF@ConfamBuy.com  ',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.email', 'staff@confambuy.com')
            ->assertJsonPath('data.invite_sent', true);

        $this->assertDatabaseHas('staff_allows', [
            'email' => 'staff@confambuy.com',
        ]);

        Notification::assertSentOnDemand(StaffRegistrationInvite::class, function (StaffRegistrationInvite $notification, array $channels, object $notifiable): bool {
            return in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === 'staff@confambuy.com'
                && $notification->registrationUrl === 'https://confambuy.test/register'
                && $notification->queue === 'notifications'
                && $notification->connection === 'database';
        });
    }

    public function test_admin_can_remove_staff_email_from_allowlist(): void
    {
        $adminRole = Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $staffAllow = StaffAllow::query()->create([
            'email' => 'staff@confambuy.com',
        ]);

        Sanctum::actingAs($admin);

        $this->deleteJson('/api/v1/admin/staff-allows/'.$staffAllow->id)
            ->assertOk()
            ->assertJsonPath('message', 'Staff email removed from allowlist.');

        $this->assertDatabaseMissing('staff_allows', [
            'id' => $staffAllow->id,
        ]);
    }

    public function test_non_admin_cannot_manage_staff_allowlist(): void
    {
        Notification::fake();

        $customerRole = Role::findOrCreate('customer', 'web');
        $customer = User::factory()->create();
        $customer->assignRole($customerRole);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/admin/staff-allows', [
            'email' => 'staff@confambuy.com',
        ])->assertForbidden();

        Notification::assertNothingSent();
    }

    public function test_duplicate_staff_email_is_rejected_without_sending_invite(): void
    {
        Notification::fake();

        $adminRole = Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        StaffAllow::query()->create([
            'email' => 'staff@confambuy.com',
        ]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/admin/staff-allows', [
            'email' => 'STAFF@confambuy.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');

        Notification::assertNothingSent();
    }
}
