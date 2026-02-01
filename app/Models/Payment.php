<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'gateway',
        'gateway_id',
        'amount',
        'currency',
        'status',
        'payload',
        'method_details',
        'refunded_amount',
        'captured'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payload' => 'array',
        'method_details' => 'array',
        'refunded_amount' => 'decimal:2',
        'captured' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }
}
