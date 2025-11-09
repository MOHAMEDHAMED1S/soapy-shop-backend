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
        Schema::create('spin_wheel_items', function (Blueprint $table) {
            $table->id();
            $table->string('text'); // النص المعروض على العجلة
            $table->string('discount_code')->nullable(); // كود الخصم (يمكن أن يكون null للعناصر بدون خصم)
            $table->decimal('probability', 5, 2)->default(0); // نسبة الحظ (0-100)
            $table->integer('order')->default(0); // ترتيب العنصر في العجلة
            $table->boolean('is_active')->default(true); // حالة التفعيل
            $table->text('description')->nullable(); // وصف إضافي
            $table->timestamps();
            
            // Indexes
            $table->index('is_active');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spin_wheel_items');
    }
};
