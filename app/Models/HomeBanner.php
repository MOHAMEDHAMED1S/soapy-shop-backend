<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeBanner extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope to get active banners
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
