<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'payment_method',
        'invoice_reference',
        'payment_id', // MyFatoorah PaymentId for duplicate prevention
        'amount',
        'currency',
        'status',
        'response_raw',
    ];

    protected $casts = [
        'response_raw' => 'array',
        'amount' => 'decimal:3',
    ];

    /**
     * Get the order that owns the payment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
