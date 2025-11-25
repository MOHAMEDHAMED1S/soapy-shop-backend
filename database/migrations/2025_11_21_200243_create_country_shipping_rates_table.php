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
        Schema::create('country_shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->unique()->comment('كود الدولة ISO');
            $table->decimal('rate_per_kg', 8, 3)->comment('سعر الشحن لكل كيلو بالدينار الكويتي');
            $table->boolean('is_active')->default(true)->comment('حالة التفعيل');
            $table->timestamps();

            // Indexes
            $table->index('country_code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('country_shipping_rates');
    }
};
