<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Promotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type', // e.g., 'percentage', 'fixed_amount'
        'value',
        'starts_at',
        'ends_at',
        'is_active',
        'priority',
        'conditions', // JSON rules for applicability
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'conditions' => 'array',
        'priority' => 'integer',
    ];

    /**
     * Scope a query to only include active promotions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                     });
    }
}
