<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class DiscountCode extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'minimum_order_amount',
        'maximum_discount_amount',
        'usage_limit',
        'usage_count',
        'usage_limit_per_customer',
        'is_active',
        'starts_at',
        'expires_at',
        'applicable_categories',
        'applicable_products',
        'applicable_customers',
        'first_time_customer_only',
        'new_customer_only',
        'admin_notes',
    ];

    protected $casts = [
        'value' => 'decimal:3',
        'minimum_order_amount' => 'decimal:3',
        'maximum_discount_amount' => 'decimal:3',
        'is_active' => 'boolean',
        'first_time_customer_only' => 'boolean',
        'new_customer_only' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'applicable_categories' => 'array',
        'applicable_products' => 'array',
        'applicable_customers' => 'array',
    ];

    /**
     * Get the usage records for the discount code.
     */
    public function usage(): HasMany
    {
        return $this->hasMany(DiscountCodeUsage::class, 'discount_code_id');
    }

    /**
     * Get the orders that used this discount code.
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'discount_code_usage')
                    ->withPivot(['discount_amount', 'order_amount_before_discount', 'order_amount_after_discount', 'used_at'])
                    ->withTimestamps();
    }

    /**
     * Check if the discount code is currently valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->isBefore($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->isAfter($this->expires_at)) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if the discount code is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && now()->isAfter($this->expires_at);
    }

    /**
     * Check if the discount code has reached its usage limit.
     */
    public function hasReachedUsageLimit(): bool
    {
        return $this->usage_limit && $this->usage_count >= $this->usage_limit;
    }

    /**
     * Check if the discount code can be used by a specific customer.
     */
    public function canBeUsedByCustomer(?Customer $customer = null, ?string $customerPhone = null): bool
    {
        // Check if code is valid
        if (!$this->isValid()) {
            return false;
        }

        // Check first time customer only
        if ($this->first_time_customer_only) {
            if (!$customer || $customer->total_orders > 0) {
                return false;
            }
        }

        // Check new customer only
        if ($this->new_customer_only) {
            if (!$customer || !$customer->isNew()) {
                return false;
            }
        }

        // Check specific customers
        if ($this->applicable_customers && is_array($this->applicable_customers)) {
            if ($customer && !in_array($customer->id, $this->applicable_customers)) {
                return false;
            }
        }

        // Check usage limit per customer
        if ($customer) {
            $customerUsageCount = $this->usage()
                ->where('customer_id', $customer->id)
                ->count();

            if ($customerUsageCount >= $this->usage_limit_per_customer) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate discount amount for a given order amount.
     */
    public function calculateDiscountAmount(float $orderAmount): float
    {
        if (!$this->isValid()) {
            return 0;
        }

        // Check minimum order amount
        if ($this->minimum_order_amount && $orderAmount < $this->minimum_order_amount) {
            return 0;
        }

        $discountAmount = 0;

        switch ($this->type) {
            case 'percentage':
                $discountAmount = ($orderAmount * $this->value) / 100;
                // Apply maximum discount amount if set
                if ($this->maximum_discount_amount && $discountAmount > $this->maximum_discount_amount) {
                    $discountAmount = $this->maximum_discount_amount;
                }
                break;

            case 'fixed_amount':
                $discountAmount = min($this->value, $orderAmount);
                break;

            case 'free_shipping':
                // This would be handled separately in shipping calculation
                $discountAmount = 0;
                break;
        }

        return round($discountAmount, 3);
    }

    /**
     * Check if the discount code applies to specific products/categories.
     */
    public function appliesToProducts(array $productIds, array $categoryIds = []): bool
    {
        // If no restrictions, applies to all products
        if (!$this->applicable_products && !$this->applicable_categories) {
            return true;
        }

        // Check specific products
        if ($this->applicable_products && is_array($this->applicable_products)) {
            if (array_intersect($productIds, $this->applicable_products)) {
                return true;
            }
        }

        // Check specific categories
        if ($this->applicable_categories && is_array($this->applicable_categories)) {
            if (array_intersect($categoryIds, $this->applicable_categories)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get remaining usage count.
     */
    public function getRemainingUsageAttribute(): ?int
    {
        if (!$this->usage_limit) {
            return null; // Unlimited
        }

        return max(0, $this->usage_limit - $this->usage_count);
    }

    /**
     * Get usage percentage.
     */
    public function getUsagePercentageAttribute(): float
    {
        if (!$this->usage_limit) {
            return 0;
        }

        return round(($this->usage_count / $this->usage_limit) * 100, 2);
    }

    /**
     * Get days until expiration.
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Scope for active discount codes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for valid discount codes (active and not expired).
     */
    public function scopeValid($query)
    {
        $now = now();
        return $query->where('is_active', true)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('starts_at')
                          ->orWhere('starts_at', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>=', $now);
                    });
    }

    /**
     * Scope for expired discount codes.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope for discount codes by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Generate a unique discount code.
     */
    public static function generateUniqueCode(int $length = 8): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Create a percentage discount code.
     */
    public static function createPercentageDiscount(
        string $code,
        string $name,
        float $percentage,
        array $options = []
    ): self {
        return static::create(array_merge([
            'code' => $code,
            'name' => $name,
            'type' => 'percentage',
            'value' => $percentage,
            'is_active' => true,
        ], $options));
    }

    /**
     * Create a fixed amount discount code.
     */
    public static function createFixedAmountDiscount(
        string $code,
        string $name,
        float $amount,
        array $options = []
    ): self {
        return static::create(array_merge([
            'code' => $code,
            'name' => $name,
            'type' => 'fixed_amount',
            'value' => $amount,
            'is_active' => true,
        ], $options));
    }

    /**
     * Create a free shipping discount code.
     */
    public static function createFreeShippingDiscount(
        string $code,
        string $name,
        array $options = []
    ): self {
        return static::create(array_merge([
            'code' => $code,
            'name' => $name,
            'type' => 'free_shipping',
            'value' => 0,
            'is_active' => true,
        ], $options));
    }
}