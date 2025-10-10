<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'short_description',
        'price',
        'currency',
        'is_available',
        'stock_quantity',
        'category_id',
        'images',
        'meta',
    ];

    protected $casts = [
        'images' => 'array',
        'meta' => 'array',
        'is_available' => 'boolean',
        'price' => 'decimal:3',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
