<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SupportTicketMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'attachments' => collect($this->attachments ?? [])
                ->map(fn (string $path): string => Storage::disk('public')->url($path))
                ->values()
                ->all(),
            'is_internal' => $this->is_internal,
            'user' => $this->whenLoaded('user', function (): array {
                return [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                ];
            }),
            'created_at' => $this->created_at,
        ];
    }
}
