<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaystackWebhookEvent extends Model
{
    /** @use HasFactory<\Database\Factories\PaystackWebhookEventFactory> */
    use HasFactory;

    protected $fillable = [
        'event',
        'reference',
        'event_id',
        'signature',
        'payload_hash',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
