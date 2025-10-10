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
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed_amount', 'free_shipping'])->default('percentage');
            $table->decimal('value', 10, 3); // Percentage (0-100) or fixed amount
            $table->decimal('minimum_order_amount', 10, 3)->nullable(); // Minimum order amount required
            $table->decimal('maximum_discount_amount', 10, 3)->nullable(); // Maximum discount amount (for percentage)
            $table->integer('usage_limit')->nullable(); // Total usage limit (null = unlimited)
            $table->integer('usage_count')->default(0); // Current usage count
            $table->integer('usage_limit_per_customer')->default(1); // Usage limit per customer
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable(); // Start date
            $table->timestamp('expires_at')->nullable(); // Expiration date
            $table->json('applicable_categories')->nullable(); // Specific categories (null = all)
            $table->json('applicable_products')->nullable(); // Specific products (null = all)
            $table->json('applicable_customers')->nullable(); // Specific customers (null = all)
            $table->boolean('first_time_customer_only')->default(false); // Only for first-time customers
            $table->boolean('new_customer_only')->default(false); // Only for new customers
            $table->text('admin_notes')->nullable(); // Admin notes
            $table->timestamps();
            
            // Indexes
            $table->index('code');
            $table->index('is_active');
            $table->index('starts_at');
            $table->index('expires_at');
            $table->index(['is_active', 'starts_at', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_codes');
    }
};
