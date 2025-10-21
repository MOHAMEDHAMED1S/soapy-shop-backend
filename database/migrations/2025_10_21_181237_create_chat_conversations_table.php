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
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->string('user_ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('context_data')->nullable(); // Store initial context (products, etc.)
            $table->enum('status', ['active', 'ended', 'timeout'])->default('active');
            $table->timestamp('last_activity_at')->nullable();
            $table->integer('message_count')->default(0);
            $table->timestamps();
            
            $table->index(['session_id', 'status']);
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
