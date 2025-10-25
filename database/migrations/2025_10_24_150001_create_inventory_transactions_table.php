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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            // نوع الحركة: increase (زيادة), decrease (نقصان), adjustment (تعديل يدوي)
            $table->enum('type', ['increase', 'decrease', 'adjustment'])->default('adjustment');
            
            // الكمية (+/-)
            $table->integer('quantity');
            
            // الكمية قبل وبعد الحركة
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            
            // السبب: purchase (شراء), sale (بيع), return (إرجاع), adjustment (تعديل), damage (تلف)
            $table->enum('reason', ['purchase', 'sale', 'return', 'adjustment', 'damage', 'initial_stock'])->default('adjustment');
            
            // ملاحظات
            $table->text('notes')->nullable();
            
            // مرجع (order_id إذا كانت من طلب)
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            
            // من قام بالعملية (admin)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('product_id');
            $table->index('type');
            $table->index('reason');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};

