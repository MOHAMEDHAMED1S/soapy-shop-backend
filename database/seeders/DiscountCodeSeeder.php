<?php

namespace Database\Seeders;

use App\Models\DiscountCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DiscountCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $discountCodes = [
            // Percentage discounts
            [
                'code' => 'WELCOME10',
                'name' => 'خصم ترحيبي 10%',
                'description' => 'خصم 10% للعملاء الجدد',
                'type' => 'percentage',
                'value' => 10,
                'minimum_order_amount' => 50,
                'maximum_discount_amount' => 25,
                'usage_limit' => 100,
                'usage_limit_per_customer' => 1,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addDays(30),
                'first_time_customer_only' => true,
                'admin_notes' => 'كود ترحيبي للعملاء الجدد',
            ],
            [
                'code' => 'SAVE20',
                'name' => 'وفر 20%',
                'description' => 'خصم 20% على جميع المنتجات',
                'type' => 'percentage',
                'value' => 20,
                'minimum_order_amount' => 100,
                'maximum_discount_amount' => 50,
                'usage_limit' => 50,
                'usage_limit_per_customer' => 2,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addDays(15),
                'admin_notes' => 'عرض خاص لفترة محدودة',
            ],
            [
                'code' => 'VIP30',
                'name' => 'خصم VIP 30%',
                'description' => 'خصم خاص للعملاء المميزين',
                'type' => 'percentage',
                'value' => 30,
                'minimum_order_amount' => 200,
                'maximum_discount_amount' => 100,
                'usage_limit' => 20,
                'usage_limit_per_customer' => 1,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addDays(7),
                'admin_notes' => 'كود خاص للعملاء المميزين',
            ],

            // Fixed amount discounts
            [
                'code' => 'SAVE15KWD',
                'name' => 'وفر 15 د.ك',
                'description' => 'خصم 15 دينار كويتي',
                'type' => 'fixed_amount',
                'value' => 15,
                'minimum_order_amount' => 75,
                'usage_limit' => 30,
                'usage_limit_per_customer' => 1,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addDays(20),
                'admin_notes' => 'خصم مبلغ ثابت',
            ],
            [
                'code' => 'BIGSAVE50',
                'name' => 'وفر 50 د.ك',
                'description' => 'خصم 50 دينار كويتي على الطلبات الكبيرة',
                'type' => 'fixed_amount',
                'value' => 50,
                'minimum_order_amount' => 300,
                'usage_limit' => 10,
                'usage_limit_per_customer' => 1,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addDays(10),
                'admin_notes' => 'خصم للطلبات الكبيرة',
            ],

            // Free shipping
            [
                'code' => 'FREESHIP',
                'name' => 'شحن مجاني',
                'description' => 'شحن مجاني على جميع الطلبات',
                'type' => 'free_shipping',
                'value' => 0,
                'minimum_order_amount' => 25,
                'usage_limit' => 200,
                'usage_limit_per_customer' => 3,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addDays(45),
                'admin_notes' => 'عرض الشحن المجاني',
            ],

            // Expired codes for testing
            [
                'code' => 'EXPIRED10',
                'name' => 'كود منتهي 10%',
                'description' => 'كود منتهي الصلاحية للاختبار',
                'type' => 'percentage',
                'value' => 10,
                'minimum_order_amount' => 50,
                'usage_limit' => 10,
                'usage_limit_per_customer' => 1,
                'is_active' => true,
                'starts_at' => now()->subDays(30),
                'expires_at' => now()->subDays(1),
                'admin_notes' => 'كود منتهي للاختبار',
            ],

            // Inactive codes
            [
                'code' => 'INACTIVE20',
                'name' => 'كود غير نشط 20%',
                'description' => 'كود غير نشط للاختبار',
                'type' => 'percentage',
                'value' => 20,
                'minimum_order_amount' => 100,
                'usage_limit' => 50,
                'usage_limit_per_customer' => 1,
                'is_active' => false,
                'starts_at' => now(),
                'expires_at' => now()->addDays(30),
                'admin_notes' => 'كود غير نشط للاختبار',
            ],

            // Limited usage codes
            [
                'code' => 'LIMITED5',
                'name' => 'كود محدود 5 مرات',
                'description' => 'كود يمكن استخدامه 5 مرات فقط',
                'type' => 'percentage',
                'value' => 15,
                'minimum_order_amount' => 60,
                'usage_limit' => 5,
                'usage_limit_per_customer' => 1,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addDays(14),
                'admin_notes' => 'كود محدود الاستخدام',
            ],

            // Category specific codes
            [
                'code' => 'BEAUTY15',
                'name' => 'خصم منتجات التجميل 15%',
                'description' => 'خصم على منتجات التجميل والعناية',
                'type' => 'percentage',
                'value' => 15,
                'minimum_order_amount' => 80,
                'usage_limit' => 25,
                'usage_limit_per_customer' => 2,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addDays(25),
                'applicable_categories' => [1, 2], // Assuming categories 1 and 2 are beauty related
                'admin_notes' => 'خصم خاص لمنتجات التجميل',
            ],

            // New customer only
            [
                'code' => 'NEWCUSTOMER25',
                'name' => 'خصم العملاء الجدد 25%',
                'description' => 'خصم خاص للعملاء الجدد فقط',
                'type' => 'percentage',
                'value' => 25,
                'minimum_order_amount' => 40,
                'maximum_discount_amount' => 30,
                'usage_limit' => 100,
                'usage_limit_per_customer' => 1,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addDays(60),
                'new_customer_only' => true,
                'admin_notes' => 'خصم للعملاء الجدد فقط',
            ],
        ];

        foreach ($discountCodes as $discountData) {
            DiscountCode::updateOrCreate(
                ['code' => $discountData['code']],
                $discountData
            );
        }

        $this->command->info('✅ تم إنشاء ' . count($discountCodes) . ' كود خصم تجريبي بنجاح!');
    }
}