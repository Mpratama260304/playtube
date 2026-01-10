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
        Schema::table('videos', function (Blueprint $table) {
            // Fast-start MP4 for instant playback
            $table->string('stream_path')->nullable()->after('original_path');
            
            // JSON: {360: {path, width, height, bitrate_kbps, filesize}, 720: {...}, ...}
            $table->json('renditions')->nullable()->after('stream_path');
            
            // Stream processing readiness flag
            $table->boolean('stream_ready')->default(false)->after('renditions');
            
            // Deprecate HLS columns (keep for safety, but set to null)
            // hls_master_path, hls_enabled will be ignored in code
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['stream_path', 'renditions', 'stream_ready']);
        });
    }
};
