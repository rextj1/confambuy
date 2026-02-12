<?php

namespace Tests\Feature;

use App\Jobs\ReleaseExpiredInventoryReservations;
use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\ProductSku;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseExpiredInventoryReservationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_releases_expired_reservations(): void
    {
        $sku = ProductSku::factory()->create();
        Inventory::factory()->create([
            'product_sku_id' => $sku->id,
            'quantity' => 10,
            'reserved' => 2,
            'allow_backorder' => false,
        ]);

        $reservation = InventoryReservation::create([
            'product_sku_id' => $sku->id,
            'quantity' => 2,
            'status' => 'reserved',
            'expires_at' => now()->subMinutes(5),
        ]);

        (new ReleaseExpiredInventoryReservations)->handle();

        $reservation->refresh();
        $this->assertSame('released', $reservation->status);

        $inventory = $sku->inventory()->first();
        $this->assertSame(0, $inventory->reserved);
    }
}
