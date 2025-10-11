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
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // products, customers, orders
            $table->string('format'); // csv, excel, json
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('filters')->nullable(); // Export filters
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->bigInteger('file_size')->nullable(); // File size in bytes
            $table->integer('records_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
