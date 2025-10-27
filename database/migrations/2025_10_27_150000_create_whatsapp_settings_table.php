<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, array, boolean
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // إدخال الإعدادات الافتراضية
        DB::table('whatsapp_settings')->insert([
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
                'value' => env('WHATSAPP_BASE_URL', 'https://api.ultramsg.com'),
                'type' => 'string',
                'description' => 'Base URL لـ WhatsApp API',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'admin_phones',
                'value' => json_encode([env('WHATSAPP_ADMIN_PHONE', '201062532581')]),
                'type' => 'array',
                'description' => 'أرقام الأدمن لاستقبال الإشعارات',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'delivery_phones',
                'value' => json_encode([env('WHATSAPP_DELIVERY_PHONE', '201062532581')]),
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
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_settings');
    }
};

