<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // Add processing_state column for background optimization tracking
            // This is SEPARATE from status - videos can be published while processing
            $table->string('processing_state')->default('pending')->after('processing_error');
        });

        // Update existing videos: if they have HLS, they're ready
        // If status was 'processing', change to 'published' since they have original_path
        DB::table('videos')
            ->whereNotNull('hls_master_path')
            ->update(['processing_state' => 'ready']);

        DB::table('videos')
            ->where('status', 'processing')
            ->update([
                'status' => 'published',
                'published_at' => DB::raw('COALESCE(published_at, created_at)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('processing_state');
        });
    }
};
