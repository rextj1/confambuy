<?php

namespace App\Models;

use App\Notifications\OrderStatusUpdated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'uuid',
        'order_number',
        'idempotency_key',
        'status', // pending, processing, completed, cancelled, declined
        'subtotal',
        'shipping_total',
        'tax_total',
        'discount_total',
        'grand_total',
        'currency',
        'refunded_total',
        'payment_status',
        'payment_method',
        'tax_breakdown',
        'metadata',
        'shipping_address_snapshot',
        'billing_address_snapshot',
        'shipping_method',
        'tracking_number',
        'customer_ip',
        'customer_note',
        'placed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'shipping_address_id',
        'billing_address_id',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'refunded_total' => 'decimal:2',
        'tax_breakdown' => 'array',
        'metadata' => 'array',
        'shipping_address_snapshot' => 'array',
        'billing_address_snapshot' => 'array',
        'placed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the user that placed the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the shipping address.
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    /**
     * Get the billing address.
     */
    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    /**
     * Get the payments for the order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the shipments for the order.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Boot function to handle model events.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $order): void {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }

            if (empty($order->uuid)) {
                $order->uuid = (string) Str::uuid();
            }
        });

        static::updated(function (self $order): void {
            $statusChanged = $order->wasChanged('status')
                || $order->wasChanged('shipped_at')
                || $order->wasChanged('delivered_at')
                || $order->wasChanged('cancelled_at');

            if (! $statusChanged) {
                return;
            }

            $status = $order->status;

            if ($order->wasChanged('shipped_at')) {
                $status = 'shipped';
            }

            if ($order->wasChanged('delivered_at')) {
                $status = 'delivered';
            }

            if ($order->wasChanged('cancelled_at')) {
                $status = 'cancelled';
            }

            $notification = new OrderStatusUpdated($order, $status);

            if ($order->user) {
                $order->user->notify($notification);
            }

            $guard = config('permission.defaults.guard', 'web');

            foreach (['admin', 'staff'] as $roleName) {
                if (! Role::query()->where('name', $roleName)->where('guard_name', $guard)->exists()) {
                    continue;
                }

                User::role($roleName)->get()->each(function (User $recipient) use ($notification): void {
                    $recipient->notify($notification);
                });
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-'.strtoupper(uniqid());
    }
}
