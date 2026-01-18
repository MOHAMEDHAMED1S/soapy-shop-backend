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
        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_bogo_item')->default(false)->after('product_snapshot');
            $table->unsignedBigInteger('bogo_offer_id')->nullable()->after('is_bogo_item');
            
            $table->foreign('bogo_offer_id')->references('id')->on('bogo_offers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['bogo_offer_id']);
            $table->dropColumn(['is_bogo_item', 'bogo_offer_id']);
        });
    }
};
