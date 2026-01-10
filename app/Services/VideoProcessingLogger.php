<?php

namespace App\Services;

use App\Models\Video;
use App\Models\VideoProcessingLog;
use Illuminate\Support\Facades\Log;

class VideoProcessingLogger
{
    /**
     * Log a processing message for a video.
     * Updated for new schema: job_type, status, metadata (no level column)
     */
    public function log(
        Video $video,
        string $level,
        string $message,
        array $context = [],
        string $job = 'hls'
    ): VideoProcessingLog {
        // Map level to status for new schema
        $status = match ($level) {
            'error' => 'failed',
            'warning' => 'processing',
            default => 'processing',
        };

        // Write to database with new column names
        $logEntry = VideoProcessingLog::create([
            'video_id' => $video->id,
            'job_type' => $job,
            'status' => $status,
            'message' => $message,
            'metadata' => !empty($context) ? $context : null,
            'created_at' => now(),
        ]);

        // Also write to Laravel log
        $logContext = array_merge([
            'video_id' => $video->id,
            'uuid' => $video->uuid,
            'job_type' => $job,
        ], $context);

        match ($level) {
            'error' => Log::error("[VideoProcessing] {$message}", $logContext),
            'warning' => Log::warning("[VideoProcessing] {$message}", $logContext),
            default => Log::info("[VideoProcessing] {$message}", $logContext),
        };

        return $logEntry;
    }

    /**
     * Log an info message.
     */
    public function info(Video $video, string $message, array $context = [], string $job = 'hls'): VideoProcessingLog
    {
        return $this->log($video, 'info', $message, $context, $job);
    }

    /**
     * Log a warning message.
     */
    public function warn(Video $video, string $message, array $context = [], string $job = 'hls'): VideoProcessingLog
    {
        return $this->log($video, 'warning', $message, $context, $job);
    }

    /**
     * Log an error message.
     */
    public function error(Video $video, string $message, array $context = [], string $job = 'hls'): VideoProcessingLog
    {
        return $this->log($video, 'error', $message, $context, $job);
    }

    /**
     * Log progress update.
     */
    public function progress(Video $video, int $percent, string $stage = '', string $job = 'hls'): VideoProcessingLog
    {
        $message = $stage ? "Progress: {$percent}% - {$stage}" : "Progress: {$percent}%";
        return $this->info($video, $message, ['progress' => $percent, 'stage' => $stage], $job);
    }

    /**
     * Clean old logs for a video (keep last N entries).
     */
    public function cleanup(Video $video, int $keepLast = 500, ?string $job = null): int
    {
        $query = VideoProcessingLog::where('video_id', $video->id);
        
        if ($job) {
            $query->where('job_type', $job);
        }

        $total = $query->count();
        
        if ($total <= $keepLast) {
            return 0;
        }

        $toDelete = $total - $keepLast;
        
        $oldestToKeep = VideoProcessingLog::where('video_id', $video->id)
            ->when($job, fn($q) => $q->where('job_type', $job))
            ->orderBy('created_at', 'desc')
            ->skip($keepLast)
            ->first();

        if ($oldestToKeep) {
            return VideoProcessingLog::where('video_id', $video->id)
                ->when($job, fn($q) => $q->where('job_type', $job))
                ->where('created_at', '<', $oldestToKeep->created_at)
                ->delete();
        }

        return 0;
    }
}
