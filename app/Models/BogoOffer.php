<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class BogoOffer extends Model
{
    protected $fillable = [
        'name',
        'description',
        'buy_product_id',
        'buy_quantity',
        'get_product_id',
        'get_quantity',
        'get_discount_type',
        'get_discount_value',
        'max_uses_per_order',
        'total_usage_limit',
        'usage_count',
        'starts_at',
        'expires_at',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'buy_quantity' => 'integer',
        'get_quantity' => 'integer',
        'get_discount_value' => 'decimal:3',
        'max_uses_per_order' => 'integer',
        'total_usage_limit' => 'integer',
        'usage_count' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    protected $appends = [
        'is_currently_active',
        'status_text',
        'formatted_offer',
    ];

    /**
     * Get the product that must be bought.
     */
    public function buyProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'buy_product_id');
    }

    /**
     * Get the product that is given for free/discounted.
     */
    public function getProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'get_product_id');
    }

    /**
     * Scope for active offers.
     */
    public function scopeActive($query)
    {
        $now = Carbon::now();
        
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('total_usage_limit')
                  ->orWhereColumn('usage_count', '<', 'total_usage_limit');
            });
    }

    /**
     * Scope for offers that apply to a specific product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('buy_product_id', $productId);
    }

    /**
     * Check if offer is currently active.
     */
    public function getIsCurrentlyActiveAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        // Check start date
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        // Check expiry date
        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        // Check usage limit
        if ($this->total_usage_limit && $this->usage_count >= $this->total_usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Get status text.
     */
    public function getStatusTextAttribute(): string
    {
        if (!$this->is_active) {
            return 'معطل';
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return 'قادم';
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return 'منتهي';
        }

        if ($this->total_usage_limit && $this->usage_count >= $this->total_usage_limit) {
            return 'استُنفد';
        }

        return 'نشط';
    }

    /**
     * Get formatted offer description.
     */
    public function getFormattedOfferAttribute(): string
    {
        $buyProduct = $this->buyProduct?->title ?? 'Product';
        $getProduct = $this->getProduct?->title ?? 'Product';
        
        $isSameProduct = $this->buy_product_id === $this->get_product_id;
        
        if ($this->get_discount_type === 'free') {
            if ($isSameProduct) {
                return "اشترِ {$this->buy_quantity} واحصل على {$this->get_quantity} مجاناً";
            }
            return "اشترِ {$this->buy_quantity} {$buyProduct} واحصل على {$this->get_quantity} {$getProduct} مجاناً";
        }
        
        if ($this->get_discount_type === 'percentage') {
            return "اشترِ {$this->buy_quantity} واحصل على {$this->get_quantity} بخصم {$this->get_discount_value}%";
        }
        
        return "اشترِ {$this->buy_quantity} واحصل على {$this->get_quantity} بخصم {$this->get_discount_value} د.ك";
    }

    /**
     * Calculate how many free items are earned for a given cart quantity.
     */
    public function calculateFreeItems(int $cartQuantity): int
    {
        if (!$this->is_currently_active) {
            return 0;
        }

        if ($cartQuantity < $this->buy_quantity) {
            return 0;
        }

        // How many times the offer is triggered
        $timesTriggered = floor($cartQuantity / $this->buy_quantity);
        $freeItems = $timesTriggered * $this->get_quantity;

        // Apply max uses per order limit
        if ($this->max_uses_per_order) {
            $maxFreeItems = $this->max_uses_per_order * $this->get_quantity;
            $freeItems = min($freeItems, $maxFreeItems);
        }

        return (int) $freeItems;
    }

    /**
     * Calculate the final price for a free/discounted item.
     */
    public function calculateFinalPrice(float $originalPrice): float
    {
        if ($this->get_discount_type === 'free') {
            return 0;
        }

        if ($this->get_discount_type === 'percentage') {
            $discount = $originalPrice * ($this->get_discount_value / 100);
            return max(0, round($originalPrice - $discount, 3));
        }

        // Fixed discount
        return max(0, round($originalPrice - $this->get_discount_value, 3));
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(int $times = 1): void
    {
        $this->increment('usage_count', $times);
    }
}
