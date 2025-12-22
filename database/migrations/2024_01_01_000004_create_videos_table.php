<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('visibility')->default('public'); // public, unlisted, private
            $table->string('status')->default('processing'); // processing, published, failed
            $table->boolean('is_short')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('duration_seconds')->nullable();
            $table->string('original_path')->nullable();
            $table->string('hls_master_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('likes_count')->default(0);
            $table->unsignedBigInteger('dislikes_count')->default(0);
            $table->unsignedBigInteger('comments_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['category_id', 'status']);
            $table->index(['published_at', 'status']);
            $table->index('views_count');
            $table->index('is_short');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
