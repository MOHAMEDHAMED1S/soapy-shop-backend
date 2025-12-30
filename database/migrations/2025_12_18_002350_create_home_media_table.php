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
        Schema::create('home_media', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['hero_slide', 'video'])->default('hero_slide');
            $table->string('media_url');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->string('subtitle_ar')->nullable();
            $table->string('subtitle_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_media');
    }
};
