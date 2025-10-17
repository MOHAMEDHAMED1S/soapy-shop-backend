<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingCost extends Model
{
    protected $fillable = [
        'cost',
        'is_active'
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * الحصول على مصاريف الشحن النشطة
     */
    public static function getActiveCost()
    {
        $shipping = self::where('is_active', true)->first();
        return $shipping ? $shipping->cost : 0;
    }

    /**
     * تحديث مصاريف الشحن
     */
    public static function updateCost($cost)
    {
        // إلغاء تفعيل جميع السجلات السابقة
        self::query()->update(['is_active' => false]);
        
        // إنشاء سجل جديد أو تحديث الموجود
        return self::create([
            'cost' => $cost,
            'is_active' => true
        ]);
    }
}
