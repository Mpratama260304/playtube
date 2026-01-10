<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_processing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->string('job', 50)->default('hls'); // hls, thumbnail, duration, etc.
            $table->enum('level', ['info', 'warning', 'error'])->default('info');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['video_id', 'created_at']);
            $table->index(['video_id', 'job', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_processing_logs');
    }
};
