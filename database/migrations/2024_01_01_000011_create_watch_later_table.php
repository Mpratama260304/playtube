<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watch_later', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['user_id', 'video_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_later');
    }
};
