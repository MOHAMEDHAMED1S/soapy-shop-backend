<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingWeightTier extends Model
{
    protected $fillable = [
        'country_code',
        'max_weight_kg',
        'base_price',
        'additional_percentage'
    ];

    protected $casts = [
        'max_weight_kg' => 'decimal:3',
        'base_price' => 'decimal:3',
        'additional_percentage' => 'decimal:2'
    ];

    /**
     * العلاقة مع جدول أسعار الدول
     */
    public function countryRate(): BelongsTo
    {
        return $this->belongsTo(CountryShippingRate::class, 'country_code', 'country_code');
    }

    /**
     * البحث عن أفضل tier لوزن معين
     * @param string $countryCode كود الدولة
     * @param float $weightKg الوزن بالكيلو
     * @return ShippingWeightTier|null
     */
    public static function findTierForWeight(string $countryCode, float $weightKg): ?self
    {
        // البحث عن أصغر tier أكبر من أو يساوي الوزن
        return self::where('country_code', $countryCode)
            ->where('max_weight_kg', '>=', $weightKg)
            ->orderBy('max_weight_kg', 'asc')
            ->first();
    }

    /**
     * الحصول على آخر tier (الأكبر) لدولة معينة
     * @param string $countryCode كود الدولة
     * @return ShippingWeightTier|null
     */
    public static function getMaxTier(string $countryCode): ?self
    {
        return self::where('country_code', $countryCode)
            ->orderBy('max_weight_kg', 'desc')
            ->first();
    }

    /**
     * حساب السعر النهائي مع النسبة الإضافية
     * @return float
     */
    public function calculateFinalPrice(): float
    {
        $basePrice = (float) $this->base_price;
        $additionalPercentage = (float) $this->additional_percentage;
        
        // السعر النهائي = السعر الأساسي + (السعر الأساسي × النسبة الإضافية)
        // مثلاً: إذا كان base_price = 4 و additional_percentage = 0.80
        // النتيجة = 4 + (4 × 0.80) = 4 + 3.2 = 7.2
        $finalPrice = $basePrice * (1 + $additionalPercentage);
        
        return round($finalPrice, 3);
    }
}
