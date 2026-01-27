<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'company', 'street', 'city', 'state', 'postal_code', 'country', 'phone', 'default_shipping', 'default_billing'];

    protected $casts = [
        'default_shipping' => 'boolean',
        'default_billing' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
