<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'sale_price',
        'stock_quantity',
        'attributes',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'attributes' => 'array',           // automatically turns JSON into PHP array
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The virtual columns we can use directly in queries
     * (Laravel will use them automatically when you write ->where('color', 'Black'))
     */
    // protected $appends = [
    //     'size',
    //     'color',
    //     'material',
    // ];

    /**
     * Get the product this variant belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accessor for virtual column 'size'
     */
    public function getSizeAttribute()
    {
        return $this->attributes['size'] ?? null;
    }

    /**
     * Accessor for virtual column 'color'
     */
    public function getColorAttribute()
    {
        return $this->attributes['color'] ?? null;
    }

    /**
     * Accessor for virtual column 'material'
     */
    public function getMaterialAttribute()
    {
        return $this->attributes['material'] ?? null;
    }

    /**
     * Scope: Only active variants
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Variants that are in stock
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Get images specifically attached to this variant
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'variant_id')->ordered();
    }

    /**
     * Get the primary image for this variant (if set)
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class, 'variant_id')->primary()->ordered();
    }
}