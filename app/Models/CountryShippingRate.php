<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CountryShippingRate extends Model
{
    protected $fillable = [
        'country_code',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Get the route key for the model.
     * This allows using country_code in URLs instead of id
     */
    public function getRouteKeyName(): string
    {
        return 'country_code';
    }

    /**
     * العلاقة مع شرائح الوزن
     */
    public function tiers(): HasMany
    {
        return $this->hasMany(ShippingWeightTier::class, 'country_code', 'country_code')
            ->orderBy('max_weight_kg', 'asc');
    }

    /**
     * حساب تكلفة الشحن بناءً على الوزن والدولة
     * @param int $totalWeightGrams الوزن الإجمالي بالجرام
     * @param string $countryCode كود الدولة
     * @return array|null [shipping_cost, breakdown] أو null إذا لم يتم العثور على السعر
     */
    public static function calculateShipping(int $totalWeightGrams, string $countryCode): ?array
    {
        // التحقق من أن الدولة مفعلة
        $country = self::where('country_code', $countryCode)
            ->where('is_active', true)
            ->first();
        
        if (!$country) {
            return null;
        }

        // تحويل من جرام إلى كيلو
        $weightKg = $totalWeightGrams / 1000;

        // البحث عن أقرب tier للوزن
        $tier = ShippingWeightTier::findTierForWeight($countryCode, $weightKg);

        if (!$tier) {
            // إذا لم يوجد tier مناسب، نستخدم آخر tier ونحسب بناءً على السعر للكيلو
            $maxTier = ShippingWeightTier::getMaxTier($countryCode);
            
            if (!$maxTier) {
                return null; // لا توجد tiers على الإطلاق
            }

            // حساب السعر بناءً على آخر tier
            $pricePerKg = $maxTier->calculateFinalPrice() / (float) $maxTier->max_weight_kg;
            $shippingCost = $weightKg * $pricePerKg;

            return [
                'shipping_cost' => round($shippingCost, 3),
                'breakdown' => [
                    'matched_tier' => null,
                    'used_max_tier' => [
                        'max_weight_kg' => (float) $maxTier->max_weight_kg,
                        'base_price' => (float) $maxTier->base_price,
                        'additional_percentage' => (float) $maxTier->additional_percentage,
                        'final_price_per_kg' => round($pricePerKg, 3)
                    ],
                    'actual_weight_kg' => round($weightKg, 3),
                    'base_price' => round($weightKg * $pricePerKg, 3),
                    'additional_fee' => 0,
                    'final_price' => round($shippingCost, 3)
                ]
            ];
        }

        // حساب السعر النهائي من الـ tier
        $finalPrice = $tier->calculateFinalPrice();
        $basePrice = (float) $tier->base_price;
        $additionalFee = $basePrice * (float) $tier->additional_percentage;

        return [
            'shipping_cost' => round($finalPrice, 3),
            'breakdown' => [
                'matched_tier' => [
                    'max_weight_kg' => (float) $tier->max_weight_kg,
                    'base_price' => (float) $tier->base_price,
                    'additional_percentage' => (float) $tier->additional_percentage
                ],
                'used_max_tier' => null,
                'actual_weight_kg' => round($weightKg, 3),
                'rounded_to_tier_kg' => (float) $tier->max_weight_kg,
                'base_price' => $basePrice,
                'additional_fee' => round($additionalFee, 3),
                'final_price' => round($finalPrice, 3)
            ]
        ];
    }

    /**
     * الحصول على جميع الأسعار النشطة
     */
    public static function getActiveRates()
    {
        return self::where('is_active', true)->with('tiers')->get();
    }
}
