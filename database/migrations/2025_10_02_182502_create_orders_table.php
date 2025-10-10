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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->json('shipping_address');
            $table->decimal('total_amount', 10, 3);
            $table->string('currency', 3)->default('KWD');
            $table->enum('status', ['pending', 'awaiting_payment', 'paid', 'shipped', 'delivered', 'cancelled', 'refunded'])->default('pending');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
