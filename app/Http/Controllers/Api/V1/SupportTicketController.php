<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSupportTicketMessageRequest;
use App\Http\Requests\Api\V1\StoreSupportTicketRequest;
use App\Http\Requests\Api\V1\UpdateSupportTicketRequest;
use App\Http\Resources\SupportTicketResource;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Notifications\SupportTicketMessageReceived;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class SupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isStaff = $user->hasAnyRole(['admin', 'staff']);

        $query = SupportTicket::query()->latest();

        if (! $isStaff) {
            $query->where('user_id', $user->id);
        }

        $tickets = $query->paginate(20);

        return ApiResponse::collection(SupportTicketResource::collection($tickets));
    }

    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (! empty($data['order_id'])) {
            $order = Order::query()
                ->where('id', $data['order_id'])
                ->where('user_id', $user->id)
                ->first();

            if (! $order && $user->hasAnyRole(['admin', 'staff']) === false) {
                return ApiResponse::message('Order not found for this user.', 422);
            }
        }

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'order_id' => $data['order_id'] ?? null,
            'category' => $data['category'] ?? 'general',
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        $message = SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $data['description'],
            'attachments' => null,
            'is_internal' => false,
        ]);

        $ticket->load(['messages' => fn ($query) => $query->orderBy('created_at'), 'messages.user']);

        $this->notifyTicketParticipants($ticket, $message, false);

        return ApiResponse::resource(new SupportTicketResource($ticket), 201);
    }

    public function show(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $user = $request->user();
        $isStaff = $user->hasAnyRole(['admin', 'staff']);

        if (! $isStaff && $supportTicket->user_id !== $user->id) {
            abort(404);
        }

        $perPage = max(1, min(100, (int) $request->query('messages_per_page', 50)));
        $page = max(1, (int) $request->query('messages_page', 1));

        $messagesQuery = $supportTicket->messages()->with('user')->orderBy('created_at');

        if (! $isStaff) {
            $messagesQuery->where('is_internal', false);
        }

        $totalMessages = (clone $messagesQuery)->count();
        $messages = $messagesQuery->forPage($page, $perPage)->get();
        $supportTicket->setRelation('messages', $messages);

        return (new SupportTicketResource($supportTicket))->additional([
            'meta' => [
                'messages' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalMessages,
                ],
            ],
        ])->response();
    }

    public function storeMessage(StoreSupportTicketMessageRequest $request, SupportTicket $supportTicket): JsonResponse
    {
        $user = $request->user();
        $isStaff = $user->hasAnyRole(['admin', 'staff']);

        if (! $isStaff && $supportTicket->user_id !== $user->id) {
            abort(404);
        }

        $data = $request->validated();

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments', []) as $file) {
                $attachmentPaths[] = $file->store('support-tickets/'.$supportTicket->id, 'public');
            }
        }

        $message = SupportTicketMessage::create([
            'support_ticket_id' => $supportTicket->id,
            'user_id' => $user->id,
            'message' => $data['message'],
            'attachments' => $attachmentPaths ?: null,
            'is_internal' => $isStaff ? (bool) ($data['is_internal'] ?? false) : false,
        ]);

        if (! $isStaff) {
            $supportTicket->update([
                'status' => $supportTicket->status === 'closed' ? 'open' : 'pending',
            ]);
        } else {
            $supportTicket->update([
                'status' => $supportTicket->status === 'closed' ? 'open' : $supportTicket->status,
            ]);
        }

        $supportTicket->update([
            'last_reply_at' => now(),
        ]);

        $supportTicket->load([
            'messages' => function ($query) use ($isStaff): void {
                if (! $isStaff) {
                    $query->where('is_internal', false);
                }

                $query->orderBy('created_at');
            },
            'messages.user',
        ]);

        $this->notifyTicketParticipants($supportTicket, $message, $isStaff);

        return ApiResponse::resource(new SupportTicketResource($supportTicket), 201);
    }

    public function update(UpdateSupportTicketRequest $request, SupportTicket $supportTicket): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasAnyRole(['admin', 'staff'])) {
            abort(403);
        }

        $data = $request->validated();

        if (! empty($data['assigned_to'])) {
            $assignedUser = User::query()->find($data['assigned_to']);

            if ($assignedUser && ! $assignedUser->hasAnyRole(['admin', 'staff'])) {
                return ApiResponse::message('Assigned user must be admin or staff.', 422);
            }
        }

        $supportTicket->update([
            'status' => $data['status'] ?? $supportTicket->status,
            'priority' => $data['priority'] ?? $supportTicket->priority,
            'assigned_to' => $data['assigned_to'] ?? $supportTicket->assigned_to,
        ]);

        return ApiResponse::resource(new SupportTicketResource($supportTicket));
    }

    private function notifyTicketParticipants(SupportTicket $ticket, SupportTicketMessage $message, bool $isStaff): void
    {
        if ($message->is_internal) {
            $this->notifyStaff($ticket, $message);

            return;
        }

        if ($isStaff) {
            if ($ticket->user) {
                $ticket->user->notify(new SupportTicketMessageReceived($ticket, $message));
            }

            return;
        }

        $this->notifyStaff($ticket, $message);
    }

    private function notifyStaff(SupportTicket $ticket, SupportTicketMessage $message): void
    {
        $guard = config('permission.defaults.guard', 'web');

        $roles = collect(['admin', 'staff'])
            ->filter(fn (string $role) => Role::query()
                ->where('name', $role)
                ->where('guard_name', $guard)
                ->exists())
            ->values()
            ->all();

        if ($roles === []) {
            return;
        }

        $staff = User::role($roles)->get();

        $staff->each(function (User $recipient) use ($ticket, $message): void {
            $recipient->notify(new SupportTicketMessageReceived($ticket, $message));
        });
    }
}
