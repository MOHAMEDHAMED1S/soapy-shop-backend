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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('discount_code')->nullable()->after('notes');
            $table->decimal('discount_amount', 10, 3)->default(0)->after('discount_code');
            $table->decimal('subtotal_amount', 10, 3)->nullable()->after('discount_amount'); // Amount before discount
            $table->decimal('shipping_amount', 10, 3)->default(0)->after('subtotal_amount');
            $table->boolean('free_shipping')->default(false)->after('shipping_amount');
            
            // Index for discount code
            $table->index('discount_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['discount_code']);
            $table->dropColumn([
                'discount_code',
                'discount_amount',
                'subtotal_amount',
                'shipping_amount',
                'free_shipping'
            ]);
        });
    }
};
