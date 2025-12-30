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
        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique()->comment('Frontend session ID');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->index();
            $table->string('customer_email')->nullable();
            $table->json('cart_items')->comment('Products with quantities');
            $table->decimal('cart_total', 10, 3);
            $table->string('currency', 3)->default('KWD');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->unsignedTinyInteger('reminder_count')->default(0);
            $table->unsignedBigInteger('converted_to_order_id')->nullable();
            $table->timestamps();

            $table->foreign('converted_to_order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('set null');

            $table->index('last_activity_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
    }
};
