<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'order_id',
        'product_id',
        'product_price',
        'quantity',
        'product_snapshot',
    ];

    protected $casts = [
        'product_snapshot' => 'array',
        'product_price' => 'decimal:3',
    ];

    /**
     * Get the order that owns the order item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product that owns the order item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
