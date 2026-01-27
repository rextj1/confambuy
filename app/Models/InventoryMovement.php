<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = ['product_sku_id', 'change', 'type', 'reference_type', 'reference_id', 'location', 'performed_by', 'metadata'];

    protected $casts = [
        'change' => 'integer',
        'metadata' => 'array',
    ];

    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
