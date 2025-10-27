<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = "site_setting_{$key}";
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }
            
            return self::parseValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, string $type = 'string'): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => self::formatValue($value, $type),
                'type' => $type,
            ]
        );

        // Clear cache
        Cache::forget("site_setting_{$key}");
        
        return $setting;
    }

    /**
     * Parse value based on type
     */
    protected static function parseValue($value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            
            case 'array':
                return is_string($value) ? json_decode($value, true) : $value;
            
            case 'integer':
                return (int) $value;
            
            case 'float':
                return (float) $value;
            
            default:
                return $value;
        }
    }

    /**
     * Format value for storage
     */
    protected static function formatValue($value, string $type): string
    {
        switch ($type) {
            case 'boolean':
                return $value ? 'true' : 'false';
            
            case 'array':
                return is_array($value) ? json_encode($value) : $value;
            
            default:
                return (string) $value;
        }
    }

    /**
     * Check if orders are enabled
     */
    public static function areOrdersEnabled(): bool
    {
        return self::get('orders_enabled', true);
    }

    /**
     * Toggle orders enabled/disabled
     */
    public static function toggleOrders(): bool
    {
        $currentValue = self::areOrdersEnabled();
        $newValue = !$currentValue;
        
        self::set('orders_enabled', $newValue, 'boolean');
        
        return $newValue;
    }
}

