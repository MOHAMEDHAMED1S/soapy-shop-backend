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
        Schema::create('payment_method_settings', function (Blueprint $table) {
            $table->id();
            $table->string('payment_method_code')->unique(); // e.g., 'kn', 'vm', 'ap', etc.
            $table->string('payment_method_name_ar')->nullable(); // Arabic name
            $table->string('payment_method_name_en')->nullable(); // English name
            $table->boolean('is_enabled')->default(true); // Whether this payment method is enabled
            $table->timestamps();
            
            // Index for faster lookups
            $table->index('payment_method_code');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_method_settings');
    }
};
