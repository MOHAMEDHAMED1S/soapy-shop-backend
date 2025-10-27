<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WhatsAppSetting extends Model
{
    protected $table = 'whatsapp_settings';
    
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = "whatsapp_setting_{$key}";
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->where('is_active', true)->first();
            
            if (!$setting) {
                return $default;
            }
            
            return self::parseValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, string $type = 'string', ?string $description = null): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => self::formatValue($value, $type),
                'type' => $type,
                'description' => $description,
                'is_active' => true,
            ]
        );

        // Clear cache
        Cache::forget("whatsapp_setting_{$key}");
        
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
     * Clear all WhatsApp settings cache
     */
    public static function clearCache(): void
    {
        $settings = self::all();
        foreach ($settings as $setting) {
            Cache::forget("whatsapp_setting_{$setting->key}");
        }
    }

    /**
     * Check if WhatsApp is enabled
     */
    public static function isEnabled(): bool
    {
        return self::get('whatsapp_enabled', false);
    }

    /**
     * Get admin phones
     */
    public static function getAdminPhones(): array
    {
        $phones = self::get('admin_phones', []);
        return is_array($phones) ? array_filter($phones) : [];
    }

    /**
     * Get delivery phones
     */
    public static function getDeliveryPhones(): array
    {
        $phones = self::get('delivery_phones', []);
        return is_array($phones) ? array_filter($phones) : [];
    }

    /**
     * Check if admin notifications are enabled
     */
    public static function isAdminNotificationEnabled(): bool
    {
        return self::get('admin_notification_enabled', false);
    }

    /**
     * Check if delivery notifications are enabled
     */
    public static function isDeliveryNotificationEnabled(): bool
    {
        return self::get('delivery_notification_enabled', false);
    }

    /**
     * Get base URL
     */
    public static function getBaseUrl(): string
    {
        return self::get('whatsapp_base_url', config('services.whatsapp.base_url', 'https://api.ultramsg.com'));
    }

    /**
     * Get logo URL
     */
    public static function getLogoUrl(): string
    {
        return self::get('logo_url', 'https://soapy-bubbles.com/logo.png');
    }
}

