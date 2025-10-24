<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class ProductDiscount extends Model
{
    protected $fillable = [
        'name',
        'description',
        'discount_type',
        'discount_value',
        'apply_to',
        'is_active',
        'starts_at',
        'expires_at',
        'priority',
    ];

    protected $casts = [
        'discount_value' => 'decimal:3',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'priority' => 'integer',
    ];

    /**
     * Get the products associated with this discount (for specific products only).
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_discount_products', 'product_discount_id', 'product_id')
            ->withTimestamps();
    }

    /**
     * Check if the discount is currently active and valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        // Check start date
        if ($this->starts_at && $now->isBefore($this->starts_at)) {
            return false;
        }

        // Check expiry date
        if ($this->expires_at && $now->isAfter($this->expires_at)) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discounted price for a given price.
     */
    public function calculateDiscountedPrice(float $originalPrice): float
    {
        if (!$this->isValid()) {
            return $originalPrice;
        }

        if ($this->discount_type === 'percentage') {
            // نسبة مئوية
            $discountAmount = ($originalPrice * $this->discount_value) / 100;
            $finalPrice = $originalPrice - $discountAmount;
        } else {
            // مبلغ ثابت
            $finalPrice = $originalPrice - $this->discount_value;
        }

        // التأكد من أن السعر لا يكون سالب
        return max(0, $finalPrice);
    }

    /**
     * Calculate discount amount for a given price.
     */
    public function calculateDiscountAmount(float $originalPrice): float
    {
        if (!$this->isValid()) {
            return 0;
        }

        if ($this->discount_type === 'percentage') {
            return ($originalPrice * $this->discount_value) / 100;
        }

        return min($this->discount_value, $originalPrice);
    }

    /**
     * Check if this discount applies to a specific product.
     */
    public function appliesToProduct(int $productId): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->apply_to === 'all_products') {
            return true;
        }

        // Check if product is in the specific products list
        return $this->products()->where('product_id', $productId)->exists();
    }

    /**
     * Scope for active discounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', Carbon::now());
            });
    }

    /**
     * Scope for discounts that apply to all products.
     */
    public function scopeForAllProducts($query)
    {
        return $query->where('apply_to', 'all_products');
    }

    /**
     * Scope for discounts that apply to specific products.
     */
    public function scopeForSpecificProducts($query)
    {
        return $query->where('apply_to', 'specific_products');
    }

    /**
     * Get discount formatted for display.
     */
    public function getFormattedDiscountAttribute(): string
    {
        if ($this->discount_type === 'percentage') {
            return $this->discount_value . '%';
        }

        return $this->discount_value . ' KWD';
    }

    /**
     * Get status text.
     */
    public function getStatusTextAttribute(): string
    {
        if (!$this->is_active) {
            return 'غير نشط';
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->isBefore($this->starts_at)) {
            return 'قادم';
        }

        if ($this->expires_at && $now->isAfter($this->expires_at)) {
            return 'منتهي';
        }

        return 'نشط';
    }
}

