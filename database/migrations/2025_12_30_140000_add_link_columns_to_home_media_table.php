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
        Schema::table('home_media', function (Blueprint $table) {
            $table->enum('link_type', ['none', 'product', 'custom_url'])->default('none')->after('is_active');
            $table->foreignId('product_id')->nullable()->after('link_type')->constrained('products')->onDelete('set null');
            $table->string('link_url')->nullable()->after('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_media', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['link_type', 'product_id', 'link_url']);
        });
    }
};
