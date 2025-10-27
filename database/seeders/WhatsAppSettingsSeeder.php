<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WhatsAppSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // حذف البيانات القديمة
        DB::table('whatsapp_settings')->truncate();

        // إدخال الإعدادات الافتراضية
        $settings = [
            [
                'key' => 'whatsapp_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'تفعيل/إلغاء تفعيل جميع رسائل WhatsApp',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'whatsapp_base_url',
                'value' => env('WHATSAPP_API_URL', 'https://wapi.soapy-bubbles.com'),
                'type' => 'string',
                'description' => 'Base URL لـ WhatsApp API',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'admin_phones',
                'value' => json_encode([env('ADMIN_WHATSAPP_PHONE', '201062532581')]),
                'type' => 'array',
                'description' => 'أرقام الأدمن لاستقبال الإشعارات',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'delivery_phones',
                'value' => json_encode([env('DELIVERY_WHATSAPP_PHONE', '201062532581')]),
                'type' => 'array',
                'description' => 'أرقام المندوبين لاستقبال إشعارات التوصيل',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'admin_notification_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'تفعيل إشعارات الأدمن',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'delivery_notification_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'تفعيل إشعارات المندوبين',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'logo_url',
                'value' => 'https://soapy-bubbles.com/logo.png',
                'type' => 'string',
                'description' => 'رابط الشعار المستخدم في الرسائل',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('whatsapp_settings')->insert($settings);

        $this->command->info('✅ تم إدخال ' . count($settings) . ' إعدادات WhatsApp بنجاح!');
        
        // عرض الإعدادات المُدخلة
        $this->command->newLine();
        $this->command->info('📋 الإعدادات المُدخلة:');
        $this->command->table(
            ['Key', 'Value', 'Type', 'Active'],
            collect($settings)->map(function ($setting) {
                $value = $setting['type'] === 'array' 
                    ? json_decode($setting['value'], true) 
                    : $setting['value'];
                
                $displayValue = is_array($value) 
                    ? json_encode($value, JSON_UNESCAPED_UNICODE) 
                    : $value;
                
                return [
                    $setting['key'],
                    strlen($displayValue) > 50 ? substr($displayValue, 0, 50) . '...' : $displayValue,
                    $setting['type'],
                    $setting['is_active'] ? '✓' : '✗',
                ];
            })->toArray()
        );
    }
}

