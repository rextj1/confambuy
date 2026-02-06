<?php

namespace Tests\Unit;

use App\Models\Inventory;
use App\Models\ProductSku;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserve_groups_items_and_locks_inventory(): void
    {
        $sku = ProductSku::factory()->create();
        Inventory::factory()->create([
            'product_sku_id' => $sku->id,
            'quantity' => 10,
            'reserved' => 0,
            'allow_backorder' => false,
        ]);

        $service = app(InventoryService::class);

        $reservations = $service->reserve(collect([
            ['sku' => $sku, 'quantity' => 2],
            ['sku' => $sku, 'quantity' => 3],
        ]), 'test-session');

        $this->assertCount(1, $reservations);
        $this->assertSame(5, $reservations->first()->quantity);

        $inventory = $sku->inventory()->first();
        $this->assertSame(5, $inventory->reserved);
        $this->assertDatabaseHas('inventory_movements', [
            'product_sku_id' => $sku->id,
            'type' => 'reserve',
            'change' => 5,
        ]);
    }

    public function test_reserve_throws_when_inventory_is_insufficient(): void
    {
        $sku = ProductSku::factory()->create();
        Inventory::factory()->create([
            'product_sku_id' => $sku->id,
            'quantity' => 1,
            'reserved' => 0,
            'allow_backorder' => false,
        ]);

        $service = app(InventoryService::class);

        $this->expectException(\RuntimeException::class);
        $service->reserve(collect([
            ['sku' => $sku, 'quantity' => 2],
        ]), 'test-session');
    }

    public function test_consume_decrements_reserved_and_quantity(): void
    {
        $sku = ProductSku::factory()->create();
        $inventory = Inventory::factory()->create([
            'product_sku_id' => $sku->id,
            'quantity' => 10,
            'reserved' => 5,
            'allow_backorder' => false,
        ]);

        $service = app(InventoryService::class);
        $reservation = $service->reserve(collect([
            ['sku' => $sku, 'quantity' => 2],
        ]), 'test-session')->first();

        $service->consume($reservation);

        $inventory->refresh();
        $this->assertSame(8, $inventory->quantity);
        $this->assertSame(5, $inventory->reserved);
        $this->assertDatabaseHas('inventory_movements', [
            'product_sku_id' => $sku->id,
            'type' => 'consume',
            'change' => -2,
        ]);
    }
}
