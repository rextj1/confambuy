<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryReservation extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'session_token', 'product_sku_id', 'quantity', 'status', 'expires_at', 'metadata'];

    protected $casts = [
        'quantity' => 'integer',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
