<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // HLS-specific columns
            $table->boolean('hls_enabled')->default(false)->after('thumbnail_path');
            $table->unsignedTinyInteger('processing_progress')->nullable()->after('processing_state');
            $table->timestamp('processing_started_at')->nullable()->after('processing_progress');
            $table->timestamp('processing_finished_at')->nullable()->after('processing_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn([
                'hls_enabled',
                'processing_progress',
                'processing_started_at',
                'processing_finished_at',
            ]);
        });
    }
};
