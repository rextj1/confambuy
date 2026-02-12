<?php

namespace App\Actions\Inventory;

use App\Models\Inventory;
use App\Models\InventoryReservation;

class ReleaseInventoryReservation
{
    public function handle(InventoryReservation $reservation): void
    {
        $inventory = Inventory::query()
            ->where('product_sku_id', $reservation->product_sku_id)
            ->lockForUpdate()
            ->first();

        if (! $inventory) {
            throw new \RuntimeException('Inventory record not found.');
        }

        $inventory->reserved = max(0, $inventory->reserved - $reservation->quantity);
        $inventory->save();

        $reservation->update(['status' => 'released']);
    }
}
