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
        Schema::create('customer_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->enum('type', ['percentage', 'fixed_amount', 'free_shipping']);
            $table->decimal('value', 10, 3)->default(0); // 0 for free_shipping
            $table->decimal('minimum_order_amount', 10, 3)->nullable();
            $table->decimal('maximum_discount_amount', 10, 3)->nullable(); // Max discount for percentage type
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable(); // Admin notes
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index('customer_id');
            $table->index('is_active');
            $table->index('type');
            
            // Unique constraint - one discount per customer
            $table->unique('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_discounts');
    }
};
