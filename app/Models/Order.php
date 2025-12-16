<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'address_id',
        'order_number',
        'subtotal',
        'shipping_charge',
        'tax',
        'discount',
        'total',
        'status',
        'payment_status',
        'payment_gateway',
        'payment_id',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_charge' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'status' => 'string',
        'payment_status' => 'string',
    ];

    /**
     * Get the user who placed the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shipping address used for this order.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class);
    }

    /**
     * Get the items in this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Scope: Pending payment orders
     */
    public function scopePaymentPending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    /**
     * Scope: Confirmed orders
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Check if order is paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Generate unique order number (e.g., ORD-20251215-000123)
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD-' . now()->format('Ymd');
        $last = self::where('order_number', 'like', $prefix . '%')
                    ->orderByDesc('id')
                    ->first();

        $seq = $last ? (int) substr($last->order_number, -6) + 1 : 1;

        return $prefix . '-' . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }
}