<?php

namespace App\Jobs;

use App\Models\InventoryReservation;
use App\Services\Inventory\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReleaseExpiredInventoryReservations implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $service = app(InventoryService::class);

        InventoryReservation::query()
            ->where('status', 'reserved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(200, function ($reservations) use ($service): void {
                foreach ($reservations as $reservation) {
                    $service->release($reservation);
                }
            });
    }
}
