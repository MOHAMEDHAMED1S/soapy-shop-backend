<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeMedia extends Model
{
    protected $table = 'home_media';

    protected $fillable = [
        'type',
        'media_url',
        'sort_order',
        'is_active',
        'title_ar',
        'title_en',
        'subtitle_ar',
        'subtitle_en',
        'link_type',
        'product_id',
        'link_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'product_id' => 'integer',
    ];

    /**
     * Get the linked product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get only active media
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get hero slides
     */
    public function scopeHeroSlides($query)
    {
        return $query->where('type', 'hero_slide');
    }

    /**
     * Scope to get video
     */
    public function scopeVideo($query)
    {
        return $query->where('type', 'video');
    }

    /**
     * Scope to order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}
