<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cart';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'variant_id',
        'quantity',
        'customization_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'customization_data' => 'array', // JSON → PHP array automatically
    ];

    /**
     * Get the user that owns the cart item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant (if any).
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Scope: Get cart items for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get the current price of this cart item (sale price if exists, otherwise base/variant price)
     */
    public function getPriceAttribute(): float
    {
        if ($this->variant) {
            return $this->variant->sale_price ?? $this->variant->price;
        }

        return $this->product->sale_price ?? $this->product->base_price;
    }

    /**
     * Get the subtotal for this cart item (price × quantity)
     */
    public function getSubtotalAttribute(): float
    {
        return $this->price * $this->quantity;
    }
}