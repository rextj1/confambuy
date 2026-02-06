<?php

namespace App\Services\Inventory;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\ProductSku;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * @param  \Illuminate\Support\Collection<int, array{sku: ProductSku, quantity: int}>  $items
     * @return \Illuminate\Support\Collection<int, InventoryReservation>
     */
    public function reserve(Collection $items, ?string $sessionToken = null): Collection
    {
        if ($items->isEmpty()) {
            return collect();
        }

        $groupedItems = $items->groupBy(fn (array $item) => $item['sku']->id)
            ->map(function (Collection $group): array {
                $sku = $group->first()['sku'];
                $quantity = $group->sum(fn (array $item) => (int) $item['quantity']);

                return [
                    'sku' => $sku,
                    'quantity' => $quantity,
                ];
            })
            ->values();

        return DB::transaction(function () use ($groupedItems, $sessionToken): Collection {
            return $groupedItems->map(function (array $item) use ($sessionToken): InventoryReservation {
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

                $reservation = InventoryReservation::create([
                    'session_token' => $sessionToken,
                    'product_sku_id' => $sku->id,
                    'quantity' => $quantity,
                    'status' => 'reserved',
                    'expires_at' => now()->addMinutes(30),
                    'metadata' => [
                        'sku' => $sku->sku,
                    ],
                ]);

                InventoryMovement::create([
                    'product_sku_id' => $sku->id,
                    'change' => $quantity,
                    'type' => 'reserve',
                    'reference_type' => InventoryReservation::class,
                    'reference_id' => $reservation->id,
                    'location' => $inventory->location,
                    'performed_by' => null,
                    'metadata' => [
                        'session_token' => $sessionToken,
                    ],
                ]);

                return $reservation;
            });
        });
    }

    public function consume(InventoryReservation $reservation): void
    {
        DB::transaction(function () use ($reservation): void {
            $inventory = Inventory::query()
                ->where('product_sku_id', $reservation->product_sku_id)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                throw new \RuntimeException('Inventory record not found.');
            }

            $inventory->reserved = max(0, $inventory->reserved - $reservation->quantity);
            $inventory->quantity = max(0, $inventory->quantity - $reservation->quantity);
            $inventory->save();

            InventoryMovement::create([
                'product_sku_id' => $reservation->product_sku_id,
                'change' => -$reservation->quantity,
                'type' => 'consume',
                'reference_type' => InventoryReservation::class,
                'reference_id' => $reservation->id,
                'location' => $inventory->location,
                'performed_by' => null,
                'metadata' => [
                    'reservation_id' => $reservation->id,
                ],
            ]);

            $reservation->update(['status' => 'consumed']);
        });
    }

    public function release(InventoryReservation $reservation): void
    {
        DB::transaction(function () use ($reservation): void {
            $inventory = Inventory::query()
                ->where('product_sku_id', $reservation->product_sku_id)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                throw new \RuntimeException('Inventory record not found.');
            }

            $inventory->reserved = max(0, $inventory->reserved - $reservation->quantity);
            $inventory->save();

            InventoryMovement::create([
                'product_sku_id' => $reservation->product_sku_id,
                'change' => -$reservation->quantity,
                'type' => 'release',
                'reference_type' => InventoryReservation::class,
                'reference_id' => $reservation->id,
                'location' => $inventory->location,
                'performed_by' => null,
                'metadata' => [
                    'reservation_id' => $reservation->id,
                ],
            ]);

            $reservation->update(['status' => 'released']);
        });
    }
}
