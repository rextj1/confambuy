<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AddressApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_user_can_list_their_addresses(): void
    {
        $user = User::factory()->create();
        Address::factory()->count(2)->create(['user_id' => $user->id]);
        Address::factory()->create();

        $this->grantAddressPermissions($user);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/addresses');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_create_address_and_set_default_shipping(): void
    {
        $user = User::factory()->create();
        Address::factory()->create([
            'user_id' => $user->id,
            'default_shipping' => true,
        ]);

        $this->grantAddressPermissions($user);

        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'line_1' => '123 Main St',
            'city' => 'Lagos',
            'state' => 'LA',
            'postal_code' => '100001',
            'country' => 'Nigeria',
            'phone' => '1234567890',
            'default_shipping' => true,
        ];

        $response = $this->postJson('/api/v1/addresses', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['line_1' => '123 Main St']);

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'line_1' => '123 Main St',
            'default_shipping' => true,
        ]);

        $this->assertDatabaseCount('addresses', 2);

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'default_shipping' => false,
        ]);
    }

    public function test_address_validation_errors_are_standardized(): void
    {
        $user = User::factory()->create();

        $this->grantAddressPermissions($user);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/addresses', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['email', 'line_1', 'city', 'phone'],
            ]);
    }

    public function test_first_address_is_promoted_to_defaults_when_not_provided(): void
    {
        $user = User::factory()->create();

        $this->grantAddressPermissions($user);

        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'line_1' => '123 Main St',
            'city' => 'Lagos',
            'state' => 'LA',
            'postal_code' => '100001',
            'country' => 'Nigeria',
            'phone' => '1234567890',
        ];

        $response = $this->postJson('/api/v1/addresses', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'default_shipping' => true,
            'default_billing' => true,
        ]);
    }

    public function test_user_can_update_address_and_set_default_billing(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create([
            'user_id' => $user->id,
            'default_billing' => false,
        ]);
        Address::factory()->create([
            'user_id' => $user->id,
            'default_billing' => true,
        ]);

        $this->grantAddressPermissions($user);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/addresses/{$address->id}", [
            'city' => 'Abuja',
            'default_billing' => true,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['city' => 'Abuja']);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'default_billing' => true,
        ]);

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'default_billing' => false,
        ]);
    }

    public function test_user_cannot_view_other_users_address(): void
    {
        $user = User::factory()->create();
        $otherAddress = Address::factory()->create();

        $this->grantAddressPermissions($user);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/addresses/{$otherAddress->id}")
            ->assertNotFound();
    }

    public function test_user_can_delete_their_address(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        $this->grantAddressPermissions($user);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/addresses/{$address->id}")
            ->assertOk();

        $this->assertDatabaseMissing('addresses', [
            'id' => $address->id,
        ]);
    }

    public function test_deleting_default_address_promotes_another_default(): void
    {
        $user = User::factory()->create();
        $default = Address::factory()->create([
            'user_id' => $user->id,
            'default_shipping' => true,
            'default_billing' => true,
        ]);
        $other = Address::factory()->create(['user_id' => $user->id]);

        $this->grantAddressPermissions($user);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/addresses/{$default->id}")
            ->assertOk();

        $this->assertDatabaseHas('addresses', [
            'id' => $other->id,
            'default_shipping' => true,
            'default_billing' => true,
        ]);
    }

    private function grantAddressPermissions(User $user): void
    {
        $guard = config('permission.defaults.guard', 'web');
        $role = Role::firstOrCreate(['name' => 'customer', 'guard_name' => $guard]);

        $permissions = [
            'addresses.view',
            'addresses.create',
            'addresses.update',
            'addresses.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
        }

        $user->assignRole($role);
        $user->givePermissionTo($permissions);
    }
}
