<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_sku_id',
        'quantity',
        'reserved',
        'location',
        'low_stock_threshold',
        'allow_backorder',
        'stock_status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved' => 'integer',
        'low_stock_threshold' => 'integer',
        'allow_backorder' => 'boolean',
    ];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'product_sku_id', 'product_sku_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class, 'product_sku_id', 'product_sku_id');
    }
}
