<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'compare_at_price',
        'sku',
        'active',
        'featured',
        'taxable',
        'published_at',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'active' => 'boolean',
        'featured' => 'boolean',
        'taxable' => 'boolean',
        'published_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the categories that belong to the product.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    /**
     * Get the coupons applicable to the product.
     */
    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'coupon_product');
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the SKUs (variants) for the product.
     */
    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class);
    }

    /**
     * Get the images for the product.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    /**
     * Get the reviews for the product.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include featured products.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    /**
     * Check if the product is in stock.
     */
    public function inStock(): bool
    {
        // Stock is now managed via SKUs and Inventory, checking if any SKU has stock
        return $this->skus()->whereHas('inventory', function ($q) {
            $q->where('quantity', '>', 0);
        })->exists();
    }

    /**
     * Get the current selling price (sale price if set, otherwise regular price).
     */
    public function getSellingPriceAttribute(): string
    {
        return $this->price;
    }
}
