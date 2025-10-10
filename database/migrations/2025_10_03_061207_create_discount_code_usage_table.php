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
        Schema::create('discount_code_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discount_code_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->decimal('discount_amount', 10, 3);
            $table->decimal('order_amount_before_discount', 10, 3);
            $table->decimal('order_amount_after_discount', 10, 3);
            $table->string('customer_phone')->nullable(); // For tracking without customer account
            $table->string('customer_email')->nullable(); // For tracking without customer account
            $table->timestamp('used_at');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('discount_code_id')->references('id')->on('discount_codes')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            
            // Indexes
            $table->index('discount_code_id');
            $table->index('order_id');
            $table->index('customer_id');
            $table->index('used_at');
            $table->unique(['discount_code_id', 'order_id']); // Prevent duplicate usage per order
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_code_usage');
    }
};
