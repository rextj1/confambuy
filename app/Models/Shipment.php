<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'carrier', 'service', 'tracking_number', 'cost', 'status'];

    protected $casts = [
        'cost' => 'decimal:2',
    ];

    protected $dates = ['shipped_at', 'delivered_at'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(ShipmentItem::class);
    }
}
