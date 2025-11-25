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
        Schema::create('shipping_weight_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->comment('كود الدولة ISO');
            $table->decimal('max_weight_kg', 8, 3)->comment('الحد الأقصى للوزن بالكيلو');
            $table->decimal('base_price', 8, 3)->comment('السعر الأساسي بالدينار الكويتي');
            $table->decimal('additional_percentage', 5, 2)->default(0)->comment('النسبة الإضافية (مثلاً 0.80 = 80%)');
            $table->timestamps();

            // Indexes for better performance
            $table->index('country_code');
            $table->index(['country_code', 'max_weight_kg']);
            
            // Foreign key constraint
            $table->foreign('country_code')
                ->references('country_code')
                ->on('country_shipping_rates')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_weight_tiers');
    }
};
