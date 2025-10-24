<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    protected $appends = [
        'has_discount',
        'discount_percentage',
        'discounted_price',
        'price_before_discount',
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

    /**
     * Get the discounts associated with this product.
     */
    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(ProductDiscount::class, 'product_discount_products', 'product_id', 'product_discount_id')
            ->withTimestamps();
    }

    /**
     * Get the active discount for this product (highest priority).
     */
    public function getActiveDiscountAttribute(): ?ProductDiscount
    {
        // Get all active discounts that apply to all products
        $allProductsDiscounts = ProductDiscount::active()
            ->forAllProducts()
            ->orderBy('priority', 'desc')
            ->get();

        // Get all active discounts that specifically include this product
        $specificDiscounts = ProductDiscount::active()
            ->forSpecificProducts()
            ->whereHas('products', function ($query) {
                $query->where('product_id', $this->id);
            })
            ->orderBy('priority', 'desc')
            ->get();

        // Merge and get the highest priority discount
        $allDiscounts = $allProductsDiscounts->merge($specificDiscounts)
            ->sortByDesc('priority');

        return $allDiscounts->first();
    }

    /**
     * Check if product has an active discount.
     */
    public function getHasDiscountAttribute(): bool
    {
        return $this->active_discount !== null;
    }

    /**
     * Get discount percentage (if applicable).
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        $discount = $this->active_discount;
        
        if (!$discount) {
            return null;
        }

        if ($discount->discount_type === 'percentage') {
            return (float) $discount->discount_value;
        }

        // Calculate percentage for fixed discount
        if ($this->price > 0) {
            return round(($discount->discount_value / $this->price) * 100, 2);
        }

        return null;
    }

    /**
     * Get the discounted price.
     */
    public function getDiscountedPriceAttribute(): float
    {
        $discount = $this->active_discount;
        
        if (!$discount) {
            return (float) $this->price;
        }

        return $discount->calculateDiscountedPrice((float) $this->price);
    }

    /**
     * Get the price before discount (original price).
     */
    public function getPriceBeforeDiscountAttribute(): float
    {
        return (float) $this->price;
    }

    /**
     * Get the discount amount.
     */
    public function getDiscountAmountAttribute(): float
    {
        $discount = $this->active_discount;
        
        if (!$discount) {
            return 0;
        }

        return $discount->calculateDiscountAmount((float) $this->price);
    }
}
