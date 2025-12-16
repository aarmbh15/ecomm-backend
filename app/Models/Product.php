<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'base_price',
        'sale_price',
        'sku',
        'is_customizable',
        'is_featured',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'base_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_customizable' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the category that this product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all images for this product (including variant-specific ones)
     */
    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductImage::class)->ordered();
    }

    /**
     * Get the primary image for this product
     */
    public function primaryImage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProductImage::class)->primary()->ordered();
    }
}