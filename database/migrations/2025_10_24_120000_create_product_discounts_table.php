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
        Schema::create('product_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الخصم
            $table->text('description')->nullable(); // وصف الخصم
            $table->enum('discount_type', ['percentage', 'fixed']); // نوع الخصم: نسبة مئوية أو مبلغ ثابت
            $table->decimal('discount_value', 10, 3); // قيمة الخصم
            $table->enum('apply_to', ['all_products', 'specific_products']); // تطبيق على كل المنتجات أو منتجات محددة
            $table->boolean('is_active')->default(true); // هل الخصم نشط
            $table->timestamp('starts_at')->nullable(); // تاريخ بداية الخصم
            $table->timestamp('expires_at')->nullable(); // تاريخ انتهاء الخصم
            $table->integer('priority')->default(0); // أولوية الخصم (إذا كان هناك أكثر من خصم)
            $table->timestamps();
            
            // Indexes
            $table->index('is_active');
            $table->index(['starts_at', 'expires_at']);
            $table->index('apply_to');
        });

        // جدول العلاقة بين الخصومات والمنتجات المحددة
        Schema::create('product_discount_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_discount_id')->constrained('product_discounts')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->timestamps();
            
            // Unique constraint لمنع التكرار
            $table->unique(['product_discount_id', 'product_id']);
            
            // Indexes
            $table->index('product_discount_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_discount_products');
        Schema::dropIfExists('product_discounts');
    }
};

