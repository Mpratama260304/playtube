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
        Schema::table('video_processing_logs', function (Blueprint $table) {
            // Rename 'job' to 'job_type'
            $table->renameColumn('job', 'job_type');
            
            // Drop level column (not needed)
            $table->dropColumn('level');
            
            // Add new columns for tracking job status and progress
            $table->string('status')->default('pending')->after('job_type');
            $table->integer('progress')->default(0)->after('context');
            $table->timestamp('started_at')->nullable()->after('progress');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->timestamp('updated_at')->nullable()->after('created_at');
            
            // Rename 'context' to 'metadata'
            $table->renameColumn('context', 'metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_processing_logs', function (Blueprint $table) {
            $table->renameColumn('metadata', 'context');
            $table->renameColumn('job_type', 'job');
            $table->enum('level', ['info', 'warning', 'error'])->default('info')->after('job');
            $table->dropColumn(['status', 'progress', 'started_at', 'completed_at', 'updated_at']);
        });
    }
};
