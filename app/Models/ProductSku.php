<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSku extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_skus';

    protected $fillable = ['product_id', 'sku', 'title', 'price', 'weight', 'attributes', 'active'];

    protected $casts = [
        'price' => 'decimal:2',
        'weight' => 'decimal:3',
        'attributes' => 'array',
        'active' => 'boolean',
        'length' => 'decimal:3',
        'width' => 'decimal:3',
        'height' => 'decimal:3',
        'cost' => 'decimal:2',
        'manage_stock' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }
}
