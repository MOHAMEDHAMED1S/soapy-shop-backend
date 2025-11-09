<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpinWheelResult extends Model
{
    protected $fillable = [
        'spin_wheel_item_id',
        'user_name',
        'user_phone',
        'discount_code',
        'text',
    ];

    /**
     * Get the spin wheel item that this result belongs to.
     */
    public function spinWheelItem(): BelongsTo
    {
        return $this->belongsTo(SpinWheelItem::class, 'spin_wheel_item_id');
    }

    /**
     * Scope for recent results.
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope for results by phone.
     */
    public function scopeByPhone($query, string $phone)
    {
        return $query->where('user_phone', $phone);
    }
}
