<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'date_of_birth',
        'gender',
        'nationality',
        'preferred_language',
        'is_active',
        'email_verified',
        'phone_verified',
        'last_order_at',
        'total_orders',
        'total_spent',
        'average_order_value',
        'preferences',
        'notes',
    ];

    protected $casts = [
        'address' => 'array',
        'preferences' => 'array',
        'is_active' => 'boolean',
        'email_verified' => 'boolean',
        'phone_verified' => 'boolean',
        'date_of_birth' => 'date',
        'last_order_at' => 'datetime',
        'total_spent' => 'decimal:3',
        'average_order_value' => 'decimal:3',
    ];

    /**
     * Get the orders for the customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the latest order for the customer.
     */
    public function latestOrder(): HasOne
    {
        return $this->hasOne(Order::class)->latest();
    }

    /**
     * Get the customer's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get the customer's age.
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }

        return $this->date_of_birth->age;
    }

    /**
     * Get the customer's lifetime value.
     */
    public function getLifetimeValueAttribute(): float
    {
        return $this->total_spent;
    }

    /**
     * Check if customer is new (first order within last 30 days).
     */
    public function isNew(): bool
    {
        return $this->total_orders <= 1 && 
               $this->created_at->isAfter(Carbon::now()->subDays(30));
    }

    /**
     * Check if customer is VIP (high spending).
     */
    public function isVip(): bool
    {
        return $this->total_spent >= 500; // 500 KWD threshold
    }

    /**
     * Check if customer is active (ordered within last 90 days).
     */
    public function isActive(): bool
    {
        return $this->is_active && 
               $this->last_order_at && 
               $this->last_order_at->isAfter(Carbon::now()->subDays(90));
    }

    /**
     * Update customer statistics after order.
     */
    public function updateOrderStatistics(Order $order): void
    {
        $this->increment('total_orders');
        $this->increment('total_spent', $order->total_amount);
        $this->update([
            'last_order_at' => now(),
            'average_order_value' => $this->total_spent / $this->total_orders
        ]);
    }

    /**
     * Find or create customer by phone.
     */
    public static function findOrCreateByPhone(string $phone, array $data = []): self
    {
        return static::firstOrCreate(
            ['phone' => $phone],
            array_merge([
                'name' => $data['name'] ?? 'عميل جديد',
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
            ], $data)
        );
    }

    /**
     * Scope for active customers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for VIP customers.
     */
    public function scopeVip($query)
    {
        return $query->where('total_spent', '>=', 500);
    }

    /**
     * Scope for new customers.
     */
    public function scopeNew($query)
    {
        return $query->where('total_orders', '<=', 1)
                    ->where('created_at', '>=', Carbon::now()->subDays(30));
    }

    /**
     * Scope for customers with recent orders.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('last_order_at', '>=', Carbon::now()->subDays($days));
    }
}
