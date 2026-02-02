<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_spend',
        'max_discount',
        'usage_limit',
        'limit_per_user',
        'used_count',
        'is_active',
        'starts_at',
        'expires_at',
        'constraints',
        'name',
        'description',
        'is_automatic'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'constraints' => 'array',
        'minimum_order_amount' => 'decimal:2',
    ];
}
