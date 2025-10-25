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
        Schema::table('products', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('products', 'has_inventory')) {
                // هل المنتج له مخزون - default false للمنتجات الحالية
                $table->boolean('has_inventory')->default(false)->after('is_available');
            }
            
            // stock_quantity already exists, skip it
            // We'll modify it in a separate statement if needed
            
            if (!Schema::hasColumn('products', 'low_stock_threshold')) {
                // الحد الأدنى للمخزون (لتنبيه الإدارة)
                $table->integer('low_stock_threshold')->default(10)->after('stock_quantity');
            }
            
            if (!Schema::hasColumn('products', 'stock_last_updated_at')) {
                // تاريخ آخر تحديث للمخزون
                $table->timestamp('stock_last_updated_at')->nullable()->after('low_stock_threshold');
            }
        });
        
        // Modify stock_quantity to be nullable if it isn't already
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock_quantity')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Only drop columns that we added (not stock_quantity as it existed before)
            if (Schema::hasColumn('products', 'has_inventory')) {
                $table->dropColumn('has_inventory');
            }
            if (Schema::hasColumn('products', 'low_stock_threshold')) {
                $table->dropColumn('low_stock_threshold');
            }
            if (Schema::hasColumn('products', 'stock_last_updated_at')) {
                $table->dropColumn('stock_last_updated_at');
            }
            // Note: We don't drop stock_quantity as it existed before this migration
        });
    }
};

