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
        'country_code',
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

    /**
     * Deduct inventory for all order items.
     * Only deducts if product has inventory tracking enabled.
     */
    public function deductInventory(): array
    {
        $results = [
            'success' => true,
            'deducted' => [],
            'skipped' => [],
            'errors' => []
        ];

        foreach ($this->orderItems as $orderItem) {
            $product = $orderItem->product;

            // Skip if product doesn't exist
            if (!$product) {
                $results['errors'][] = [
                    'order_item_id' => $orderItem->id,
                    'error' => 'Product not found'
                ];
                continue;
            }

            // Skip if product doesn't track inventory
            if (!$product->has_inventory) {
                $results['skipped'][] = [
                    'product_id' => $product->id,
                    'product_title' => $product->title,
                    'reason' => 'Product does not track inventory'
                ];
                continue;
            }

            // Attempt to deduct inventory
            $deducted = $product->decreaseStock(
                $orderItem->quantity,
                $this->id,
                null,
                "Deducted for order #{$this->order_number}"
            );

            if ($deducted) {
                $results['deducted'][] = [
                    'product_id' => $product->id,
                    'product_title' => $product->title,
                    'quantity' => $orderItem->quantity,
                    'remaining_stock' => $product->stock_quantity
                ];
            } else {
                $results['success'] = false;
                $results['errors'][] = [
                    'product_id' => $product->id,
                    'product_title' => $product->title,
                    'quantity_requested' => $orderItem->quantity,
                    'current_stock' => $product->stock_quantity,
                    'error' => 'Insufficient stock'
                ];
            }
        }

        return $results;
    }
}
