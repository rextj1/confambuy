<?php

namespace Tests\Feature;

use App\Models\StaffAllow;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApiRegistrationTest extends TestCase
{
    use RefreshDatabase;

    // Clear permission cache before each test to ensure consistent role/permission behavior 
    // setUp method helps to dry up to prevent repetition
    protected function setUp(): void
    {
        // 
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_registration_creates_unverified_user_without_role_and_dispatches_registered_event(): void
    {
        // Fake the Registered event to assert it was dispatched without actually sending emails
        Event::fake([Registered::class]);

        $response = $this->postJson('/api/register', [
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'phone' => '08012345678',
            'password' => 'Password',
            'password_confirmation' => 'Password',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('verification_email_sent', true)
            ->assertJsonPath('user.email', 'customer@example.com');

        $registeredUser = User::query()->where('email', 'customer@example.com')->firstOrFail();

        $this->assertFalse($registeredUser->hasVerifiedEmail());
        $this->assertFalse($registeredUser->hasRole('staff'));
        $this->assertFalse($registeredUser->hasRole('customer'));
        Event::assertDispatched(Registered::class);
    }

    public function test_verified_user_is_assigned_customer_role_by_default(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'phone' => '08012345678',
            'password' => 'Password',
            'password_confirmation' => 'Password',
        ])->assertStatus(201);

        $registeredUser = User::query()->where('email', 'customer@example.com')->firstOrFail();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $registeredUser->id,
                'hash' => sha1($registeredUser->getEmailForVerification()),
            ],
        );

        $this->getJson($verificationUrl)
            ->assertOk()
            ->assertJsonPath('message', 'Email verified successfully.');

        $registeredUser->refresh();

        $this->assertTrue($registeredUser->hasVerifiedEmail());
        $this->assertFalse($registeredUser->hasRole('staff'));
        $this->assertTrue($registeredUser->hasRole('customer'));
    }

    public function test_verified_allowlisted_user_is_assigned_staff_role(): void
    {
        StaffAllow::query()->create(['email' => 'staff@confambuy.com']);

        $this->postJson('/api/register', [
            'name' => 'Staff User',
            'email' => 'staff@confambuy.com',
            'phone' => '08011112222',
            'password' => 'Password',
            'password_confirmation' => 'Password',
        ])->assertStatus(201);

        $registeredUser = User::query()->where('email', 'staff@confambuy.com')->firstOrFail();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $registeredUser->id,
                'hash' => sha1($registeredUser->getEmailForVerification()),
            ],
        );

        $this->getJson($verificationUrl)->assertOk();

        $registeredUser->refresh();

        $this->assertTrue($registeredUser->hasVerifiedEmail());
        $this->assertTrue($registeredUser->hasRole('staff'));
        $this->assertFalse($registeredUser->hasRole('customer'));
    }

    public function test_verified_registration_does_not_allow_client_to_self_assign_staff_role(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Malicious User',
            'email' => 'malicious@example.com',
            'phone' => '08033334444',
            'role' => 'staff',
            'password' => 'Password',
            'password_confirmation' => 'Password',
        ])->assertStatus(201);

        $registeredUser = User::query()->where('email', 'malicious@example.com')->firstOrFail();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $registeredUser->id,
                'hash' => sha1($registeredUser->getEmailForVerification()),
            ],
        );

        $this->getJson($verificationUrl)->assertOk();

        $registeredUser->refresh();

        $this->assertTrue($registeredUser->hasRole('customer'));
        $this->assertFalse($registeredUser->hasRole('staff'));
    }

    public function test_email_verification_link_with_invalid_hash_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1('another-email@example.com'),
            ],
        );

        $this->getJson($verificationUrl)->assertForbidden();
    }

    public function test_unverified_authenticated_user_can_request_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('message', 'Verification link sent.');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verified_user_cannot_request_another_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('message', 'Email already verified.');

        Notification::assertNothingSent();
    }
}
