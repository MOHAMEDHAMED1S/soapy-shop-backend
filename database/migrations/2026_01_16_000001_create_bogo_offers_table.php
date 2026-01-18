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
        Schema::create('bogo_offers', function (Blueprint $table) {
            $table->id();
            $table->string('name');                           // "Buy 2 Soaps Get 1 Free"
            $table->text('description')->nullable();
            
            // Buy requirements
            $table->unsignedBigInteger('buy_product_id');     // Product to BUY
            $table->integer('buy_quantity')->default(1);      // How many to buy
            
            // Get rewards
            $table->unsignedBigInteger('get_product_id');     // Product to GET (can be same or different)
            $table->integer('get_quantity')->default(1);      // How many free items
            $table->enum('get_discount_type', ['free', 'percentage', 'fixed'])->default('free');
            $table->decimal('get_discount_value', 10, 3)->default(100.000); // 100% = free
            
            // Limits
            $table->integer('max_uses_per_order')->nullable(); // Max free items per order
            $table->integer('total_usage_limit')->nullable();  // Total times offer can be used
            $table->integer('usage_count')->default(0);
            
            // Scheduling
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            
            $table->timestamps();
            
            $table->foreign('buy_product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('get_product_id')->references('id')->on('products')->onDelete('cascade');
            
            // Indexes for performance
            $table->index(['is_active', 'starts_at', 'expires_at']);
            $table->index('buy_product_id');
            $table->index('get_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bogo_offers');
    }
};
