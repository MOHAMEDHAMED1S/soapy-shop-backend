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
        Schema::create('chatbot_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('AI Assistant');
            $table->text('system_prompt');
            $table->text('welcome_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('product_access_type', ['all', 'specific'])->default('all');
            $table->json('allowed_product_ids')->nullable(); // For specific products
            $table->json('ai_settings')->nullable(); // For AI model settings
            $table->integer('max_conversation_length')->default(50);
            $table->integer('token_limit_per_message')->default(4000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_settings');
    }
};
