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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->nullable()->unique();
            $table->json('address')->nullable(); // Default address
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('nationality')->nullable();
            $table->string('preferred_language', 5)->default('ar');
            $table->boolean('is_active')->default(true);
            $table->boolean('email_verified')->default(false);
            $table->boolean('phone_verified')->default(false);
            $table->timestamp('last_order_at')->nullable();
            $table->integer('total_orders')->default(0);
            $table->decimal('total_spent', 10, 3)->default(0);
            $table->decimal('average_order_value', 10, 3)->default(0);
            $table->json('preferences')->nullable(); // Customer preferences
            $table->text('notes')->nullable(); // Admin notes
            $table->timestamps();
            
            // Indexes
            $table->index('phone');
            $table->index('email');
            $table->index('is_active');
            $table->index('last_order_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
