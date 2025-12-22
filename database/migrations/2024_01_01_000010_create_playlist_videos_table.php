<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlist_videos', function (Blueprint $table) {
            $table->foreignId('playlist_id')->constrained()->onDelete('cascade');
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['playlist_id', 'video_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_videos');
    }
};
