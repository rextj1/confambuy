<?php

namespace App\Actions\Inventory;

use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\ProductSku;
use Illuminate\Support\Collection;

class ReserveInventory
{
    /**
     * @param  \Illuminate\Support\Collection<int, array{sku: ProductSku, quantity: int}>  $items
     * @return \Illuminate\Support\Collection<int, InventoryReservation>
     */
    public function handle(Collection $items, ?string $sessionToken = null): Collection
    {
        return $items->map(function (array $item) use ($sessionToken): InventoryReservation {
            /** @var ProductSku $sku */
            $sku = $item['sku'];
            $quantity = (int) $item['quantity'];

            $inventory = Inventory::query()
                ->where('product_sku_id', $sku->id)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                throw new \RuntimeException('Inventory record not found.');
            }

            $available = $inventory->quantity - $inventory->reserved;

            if (! $inventory->allow_backorder && $available < $quantity) {
                throw new \RuntimeException('Insufficient inventory.');
            }

            $inventory->reserved += $quantity;
            $inventory->save();

            return InventoryReservation::create([
                'session_token' => $sessionToken,
                'product_sku_id' => $sku->id,
                'quantity' => $quantity,
                'status' => 'reserved',
                'expires_at' => now()->addMinutes(30),
                'metadata' => [
                    'sku' => $sku->sku,
                ],
            ]);
        });
    }
}
