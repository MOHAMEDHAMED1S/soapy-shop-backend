<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDiscount extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'value',
        'minimum_order_amount',
        'maximum_discount_amount',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'value' => 'decimal:3',
        'minimum_order_amount' => 'decimal:3',
        'maximum_discount_amount' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    /**
     * Get the customer that owns this discount.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the admin user who created this discount.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate discount amount for an order.
     */
    public function calculateDiscountAmount(float $orderAmount): float
    {
        // Check minimum order amount
        if ($this->minimum_order_amount && $orderAmount < $this->minimum_order_amount) {
            return 0;
        }

        switch ($this->type) {
            case 'percentage':
                $discountAmount = $orderAmount * ($this->value / 100);
                // Apply maximum discount cap if set
                if ($this->maximum_discount_amount && $discountAmount > $this->maximum_discount_amount) {
                    $discountAmount = $this->maximum_discount_amount;
                }
                return round($discountAmount, 3);

            case 'fixed_amount':
                return min($this->value, $orderAmount);

            case 'free_shipping':
                return 0; // Shipping is handled separately

            default:
                return 0;
        }
    }

    /**
     * Check if this discount provides free shipping.
     */
    public function providesFreeShipping(): bool
    {
        return $this->type === 'free_shipping';
    }

    /**
     * Check if this discount is applicable for the given order amount.
     */
    public function isApplicable(float $orderAmount): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->minimum_order_amount && $orderAmount < $this->minimum_order_amount) {
            return false;
        }

        return true;
    }

    /**
     * Get discount type label in Arabic.
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'percentage' => 'نسبة مئوية',
            'fixed_amount' => 'مبلغ ثابت',
            'free_shipping' => 'شحن مجاني',
            default => $this->type,
        };
    }

    /**
     * Scope for active discounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Find active discount by customer phone.
     */
    public static function findByPhone(string $phone): ?self
    {
        $customer = Customer::where('phone', $phone)->first();
        
        if (!$customer) {
            return null;
        }

        return static::where('customer_id', $customer->id)
            ->where('is_active', true)
            ->first();
    }
}
