<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AbandonedCart extends Model
{
    protected $fillable = [
        'session_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'cart_items',
        'cart_total',
        'currency',
        'last_activity_at',
        'reminder_sent_at',
        'reminder_count',
        'converted_to_order_id',
    ];

    protected $casts = [
        'cart_items' => 'array',
        'cart_total' => 'decimal:3',
        'last_activity_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'reminder_count' => 'integer',
    ];

    /**
     * Get the order this cart was converted to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_to_order_id');
    }

    /**
     * Check if cart is converted to order
     */
    public function isConverted(): bool
    {
        return $this->converted_to_order_id !== null;
    }

    /**
     * Get items count
     */
    public function getItemsCountAttribute(): int
    {
        return collect($this->cart_items)->sum('quantity');
    }

    /**
     * Get hours since last activity
     */
    public function getHoursSinceActivityAttribute(): int
    {
        if (!$this->last_activity_at) {
            return Carbon::parse($this->created_at)->diffInHours(now());
        }
        return Carbon::parse($this->last_activity_at)->diffInHours(now());
    }

    /**
     * Scope for not converted carts
     */
    public function scopeNotConverted($query)
    {
        return $query->whereNull('converted_to_order_id');
    }

    /**
     * Scope for carts older than X hours
     */
    public function scopeOlderThan($query, int $hours)
    {
        return $query->where('last_activity_at', '<', now()->subHours($hours));
    }

    /**
     * Scope for carts with reminder not sent
     */
    public function scopeNoReminder($query)
    {
        return $query->whereNull('reminder_sent_at');
    }

    /**
     * Mark as reminder sent
     */
    public function markReminderSent(): void
    {
        $this->update([
            'reminder_sent_at' => now(),
            'reminder_count' => $this->reminder_count + 1,
        ]);
    }

    /**
     * Mark as converted to order
     */
    public function markConverted(int $orderId): void
    {
        $this->update([
            'converted_to_order_id' => $orderId,
        ]);
    }

    /**
     * Generate WhatsApp URL with reminder message
     */
    public function getWhatsAppUrl(): string
    {
        $phone = preg_replace('/[^0-9]/', '', $this->customer_phone);
        
        // Build products list
        $productsList = collect($this->cart_items)->map(function ($item) {
            return "• {$item['title']} (x{$item['quantity']})";
        })->implode("\n");

        $message = "مرحباً {$this->customer_name} 👋\n\n";
        $message .= "لاحظنا أنك تركت سلة تسوق بقيمة {$this->cart_total} {$this->currency} في متجر Soapy!\n\n";
        $message .= "المنتجات:\n{$productsList}\n\n";
        $message .= "أكمل طلبك الآن واستمتع بتجربة تسوق مميزة ✨\n\n";
        $message .= "رابط المتجر: https://Soapy-bubbles.com";

        $encodedMessage = urlencode($message);

        return "https://api.whatsapp.com/send/?phone={$phone}&text={$encodedMessage}&type=phone_number&app_absent=0";
    }
}
