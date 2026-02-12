<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupportTicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_ticket_and_add_messages(): void
    {
        Notification::fake();
        Storage::fake('public');

        $guard = config('permission.defaults.guard', 'web');
        $role = Role::firstOrCreate(['name' => 'customer', 'guard_name' => $guard]);

        $customer = User::factory()->create();
        $customer->assignRole($role);

        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/v1/tickets', [
            'subject' => 'Package missing',
            'description' => 'My package did not arrive.',
            'category' => 'shipping',
            'priority' => 'high',
        ]);

        $response->assertStatus(201)->assertJsonStructure([
            'data' => ['id', 'ticket_number', 'subject', 'messages'],
        ]);

        $ticketId = $response->json('data.id');

        $this->post('/api/v1/tickets/'.$ticketId.'/messages', [
            'message' => 'Any update?',
            'attachments' => [UploadedFile::fake()->image('photo.jpg')],
        ], ['Accept' => 'application/json'])->assertStatus(201);

        $this->getJson('/api/v1/tickets/'.$ticketId)
            ->assertOk()
            ->assertJsonFragment(['message' => 'Any update?'])
            ->assertJsonStructure([
                'meta' => [
                    'messages' => ['page', 'per_page', 'total'],
                ],
            ]);
    }

    public function test_customer_cannot_view_internal_messages(): void
    {
        Notification::fake();

        $guard = config('permission.defaults.guard', 'web');
        $role = Role::firstOrCreate(['name' => 'customer', 'guard_name' => $guard]);

        $customer = User::factory()->create();
        $customer->assignRole($role);

        $ticket = SupportTicket::factory()->create(['user_id' => $customer->id]);
        SupportTicketMessage::factory()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'message' => 'Public message',
            'is_internal' => false,
        ]);
        SupportTicketMessage::factory()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'message' => 'Internal note',
            'is_internal' => true,
        ]);

        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/tickets/'.$ticket->id)
            ->assertOk()
            ->assertJsonFragment(['message' => 'Public message'])
            ->assertJsonMissing(['message' => 'Internal note']);
    }

    public function test_staff_can_view_internal_messages(): void
    {
        Notification::fake();

        $guard = config('permission.defaults.guard', 'web');
        $role = Role::firstOrCreate(['name' => 'staff', 'guard_name' => $guard]);

        $staff = User::factory()->create();
        $staff->assignRole($role);

        $ticket = SupportTicket::factory()->create();
        SupportTicketMessage::factory()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $ticket->user_id,
            'message' => 'Internal note',
            'is_internal' => true,
        ]);

        Sanctum::actingAs($staff);

        $this->getJson('/api/v1/tickets/'.$ticket->id)
            ->assertOk()
            ->assertJsonFragment(['message' => 'Internal note']);
    }

    public function test_staff_can_assign_ticket_and_update_status(): void
    {
        Notification::fake();

        $guard = config('permission.defaults.guard', 'web');
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => $guard]);

        $staff = User::factory()->create();
        $staff->assignRole($staffRole);

        $ticket = SupportTicket::factory()->create();

        Sanctum::actingAs($staff);

        $this->patchJson('/api/v1/tickets/'.$ticket->id, [
            'status' => 'resolved',
            'priority' => 'low',
            'assigned_to' => $staff->id,
        ])->assertOk()
            ->assertJsonFragment(['status' => 'resolved']);
    }
}
