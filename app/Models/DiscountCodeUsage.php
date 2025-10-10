<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountCodeUsage extends Model
{
    protected $table = 'discount_code_usage';
    
    protected $fillable = [
        'discount_code_id',
        'order_id',
        'customer_id',
        'discount_amount',
        'order_amount_before_discount',
        'order_amount_after_discount',
        'customer_phone',
        'customer_email',
        'used_at',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:3',
        'order_amount_before_discount' => 'decimal:3',
        'order_amount_after_discount' => 'decimal:3',
        'used_at' => 'datetime',
    ];

    /**
     * Get the discount code that was used.
     */
    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    /**
     * Get the order that used the discount code.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the customer who used the discount code.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the discount percentage applied.
     */
    public function getDiscountPercentageAttribute(): float
    {
        if ($this->order_amount_before_discount <= 0) {
            return 0;
        }

        return round(($this->discount_amount / $this->order_amount_before_discount) * 100, 2);
    }

    /**
     * Get the savings amount.
     */
    public function getSavingsAttribute(): float
    {
        return $this->discount_amount;
    }

    /**
     * Scope for usage by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('used_at', [$startDate, $endDate]);
    }

    /**
     * Scope for usage by customer.
     */
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope for usage by discount code.
     */
    public function scopeByDiscountCode($query, $discountCodeId)
    {
        return $query->where('discount_code_id', $discountCodeId);
    }

    /**
     * Scope for recent usage.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('used_at', '>=', now()->subDays($days));
    }
}