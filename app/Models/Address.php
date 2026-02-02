<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'line_1',
        'line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'default_shipping',
        'default_billing',
    ];

    protected $casts = [
        'default_shipping' => 'boolean',
        'default_billing' => 'boolean',
    ];

    /**
     * Get the user that owns the address.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full address as a string.
     */
    public function getFullAddressAttribute(): string
    {
        return "{$this->line_1}, " .
               ($this->line_2 ? "{$this->line_2}, " : "") .
               "{$this->city}, {$this->state} {$this->postal_code}, {$this->country}";
    }
}