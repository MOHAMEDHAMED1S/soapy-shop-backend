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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('provider');
            $table->string('payment_method');
            $table->string('invoice_reference');
            $table->decimal('amount', 10, 3);
            $table->string('currency', 3);
            $table->enum('status', ['initiated', 'pending', 'paid', 'failed', 'refunded'])->default('initiated');
            $table->json('response_raw');
            $table->timestamps();
            
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
