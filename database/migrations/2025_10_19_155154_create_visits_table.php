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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->nullable(); // IPv4 and IPv6 support
            $table->text('user_agent')->nullable();
            $table->string('referer_url')->nullable();
            $table->string('referer_domain')->nullable();
            $table->enum('referer_type', ['facebook', 'instagram', 'twitter', 'other', 'direct'])->default('direct');
            $table->string('page_url');
            $table->string('page_title')->nullable();
            $table->string('session_id')->nullable();
            $table->string('country', 2)->nullable(); // ISO country code
            $table->string('city')->nullable();
            $table->string('device_type', 20)->nullable(); // mobile, desktop, tablet
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->boolean('is_unique')->default(true); // unique visitor flag
            $table->timestamp('visited_at');
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['referer_type', 'visited_at']);
            $table->index(['referer_domain', 'visited_at']);
            $table->index(['ip_address', 'visited_at']);
            $table->index('visited_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
