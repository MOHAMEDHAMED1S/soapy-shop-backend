<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethodSetting extends Model
{
    protected $fillable = [
        'payment_method_code',
        'payment_method_name_ar',
        'payment_method_name_en',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Get enabled payment method codes
     */
    public static function getEnabledCodes(): array
    {
        return self::where('is_enabled', true)
            ->pluck('payment_method_code')
            ->toArray();
    }

    /**
     * Check if a payment method is enabled
     */
    public static function isEnabled(string $code): bool
    {
        $setting = self::where('payment_method_code', $code)->first();
        
        // If no setting exists, consider it enabled by default
        return $setting ? $setting->is_enabled : true;
    }

    /**
     * Toggle payment method status
     */
    public static function toggle(string $code): bool
    {
        $setting = self::firstOrCreate(
            ['payment_method_code' => $code],
            ['is_enabled' => true]
        );

        $setting->is_enabled = !$setting->is_enabled;
        $setting->save();

        return $setting->is_enabled;
    }

    /**
     * Sync payment methods from MyFatoorah response
     */
    public static function syncFromMyFatoorah(array $paymentMethods): void
    {
        foreach ($paymentMethods as $method) {
            self::updateOrCreate(
                ['payment_method_code' => $method['PaymentMethodCode']],
                [
                    'payment_method_name_ar' => $method['PaymentMethodAr'],
                    'payment_method_name_en' => $method['PaymentMethodEn'],
                    // Don't change is_enabled status during sync
                ]
            );
        }
    }
}
