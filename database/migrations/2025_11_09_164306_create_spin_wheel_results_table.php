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
        Schema::create('spin_wheel_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spin_wheel_item_id')->constrained('spin_wheel_items')->onDelete('cascade');
            $table->string('user_name'); // اسم المستخدم
            $table->string('user_phone'); // رقم هاتف المستخدم
            $table->string('discount_code')->nullable(); // كود الخصم الذي فاز به
            $table->string('text'); // النص الذي ظهر
            $table->timestamps();
            
            // Indexes
            $table->index('user_phone');
            $table->index('created_at');
            $table->index('spin_wheel_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spin_wheel_results');
    }
};
