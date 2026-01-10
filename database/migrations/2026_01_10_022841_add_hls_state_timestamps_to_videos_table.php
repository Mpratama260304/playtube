<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add HLS state timestamps for proper state machine:
     * - hls_queued_at: when job was queued
     * - hls_started_at: when ffmpeg actually started (not when queued)
     * - hls_last_heartbeat_at: last time the job sent a heartbeat (for stuck detection)
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // Add HLS state timestamps for proper state machine
            $table->timestamp('hls_queued_at')->nullable()->after('processing_finished_at');
            $table->timestamp('hls_started_at')->nullable()->after('hls_queued_at');
            $table->timestamp('hls_last_heartbeat_at')->nullable()->after('hls_started_at');
        });

        // Migrate existing data: if processing_state is 'processing', set hls_started_at
        \DB::statement("
            UPDATE videos 
            SET hls_started_at = processing_started_at 
            WHERE processing_state = 'processing' AND processing_started_at IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['hls_queued_at', 'hls_started_at', 'hls_last_heartbeat_at']);
        });
    }
};
