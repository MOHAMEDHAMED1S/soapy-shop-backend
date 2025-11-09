<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpinWheelItem extends Model
{
    protected $fillable = [
        'text',
        'discount_code',
        'probability',
        'order',
        'is_active',
        'description',
    ];

    protected $casts = [
        'probability' => 'decimal:2',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get all results for this item.
     */
    public function results(): HasMany
    {
        return $this->hasMany(SpinWheelResult::class, 'spin_wheel_item_id');
    }

    /**
     * Scope for active items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered items.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}
