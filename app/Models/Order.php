<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'shipping_address',
        'total_amount',
        'currency',
        'status',
        'payment_id',
        'notes',
        'tracking_number',
        'shipping_date',
        'delivery_date',
        'admin_notes',
        'discount_code',
        'discount_amount',
        'subtotal_amount',
        'shipping_amount',
        'free_shipping',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'total_amount' => 'decimal:3',
        'discount_amount' => 'decimal:3',
        'subtotal_amount' => 'decimal:3',
        'shipping_amount' => 'decimal:3',
        'free_shipping' => 'boolean',
    ];

    /**
     * Get the order items for the order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the payment associated with the order.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Get the customer that owns the order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the discount code used in this order.
     */
    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class, 'discount_code', 'code');
    }

    /**
     * Generate a unique order number (7 digits).
     */
    public static function generateOrderNumber(): string
    {
        $maxAttempts = 100; // Prevent infinite loop
        $attempts = 0;
        
        do {
            // Generate a 7-digit number starting from 1000000
            $orderNumber = str_pad(mt_rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);
            $attempts++;
            
            // If we've tried too many times, use timestamp-based approach
            if ($attempts >= $maxAttempts) {
                $orderNumber = str_pad(mt_rand(1000000, 9999999) . substr(time(), -2), 7, '0', STR_PAD_LEFT);
                break;
            }
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
