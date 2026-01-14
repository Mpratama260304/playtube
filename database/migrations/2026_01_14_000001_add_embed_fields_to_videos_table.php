<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds support for embedded videos from external platforms.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // Video source type: 'upload' (default) or 'embed'
            $table->string('source_type')->default('upload')->after('uuid');
            
            // For embedded videos: the original URL provided by user
            $table->string('embed_url', 1024)->nullable()->after('source_type');
            
            // For embedded videos: the platform (youtube, dailymotion, googledrive, vimeo, etc.)
            $table->string('embed_platform')->nullable()->after('embed_url');
            
            // For embedded videos: the extracted video ID from the platform
            $table->string('embed_video_id')->nullable()->after('embed_platform');
            
            // For embedded videos: the processed embed iframe URL
            $table->string('embed_iframe_url', 1024)->nullable()->after('embed_video_id');
            
            // Index for efficient queries
            $table->index('source_type');
            $table->index('embed_platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['source_type']);
            $table->dropIndex(['embed_platform']);
            $table->dropColumn([
                'source_type',
                'embed_url',
                'embed_platform',
                'embed_video_id',
                'embed_iframe_url',
            ]);
        });
    }
};
