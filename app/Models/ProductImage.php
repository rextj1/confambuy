<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'product_sku_id', 'path', 'alt', 'position'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
