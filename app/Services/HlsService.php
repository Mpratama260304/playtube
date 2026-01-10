<?php

namespace App\Services;

use App\Jobs\TranscodeToHlsJob;
use App\Models\Video;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Centralized service for HLS transcoding operations.
 * This service handles job dispatch, state management, and prerequisite validation.
 * 
 * STATE MACHINE:
 *   pending -> queued -> processing -> ready
 *                    \-> failed
 * 
 * - QUEUED: job dispatched to queue, waiting for worker
 * - PROCESSING: worker picked up job, ffmpeg actively running
 * - READY: successfully completed
 * - FAILED: error occurred
 */
class HlsService
{
    protected VideoProcessingLogger $logger;

    public function __construct(VideoProcessingLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Enqueue an HLS transcoding job for a video.
     * Sets state to QUEUED and dispatches job. Does NOT set PROCESSING (that's the job's responsibility).
     * 
     * @param Video $video The video to transcode
     * @param string $reason Why the job is being queued (for logging)
     * @return array Status array with 'success', 'message', and optionally 'error'
     */
    public function enqueue(Video $video, string $reason = 'manual'): array
    {
        // Idempotency check: if already queued/processing with recent heartbeat, don't duplicate
        if ($this->isActivelyProcessing($video)) {
            return [
                'success' => false,
                'message' => 'HLS transcoding is already in progress.',
                'error' => 'already_processing',
            ];
        }

        // Check if HLS is enabled
        if (!$video->hls_enabled) {
            return [
                'success' => false,
                'message' => 'HLS is not enabled for this video.',
                'error' => 'hls_disabled',
            ];
        }

        // Check if original file exists
        if (!$video->original_path || !Storage::disk('public')->exists($video->original_path)) {
            $this->logger->error($video, 'Cannot start HLS transcoding: Original video file not found', [
                'reason' => $reason,
                'original_path' => $video->original_path,
            ]);

            $video->update([
                'processing_state' => Video::PROCESSING_FAILED,
                'processing_error' => 'Original video file not found',
                'processing_finished_at' => now(),
            ]);

            return [
                'success' => false,
                'message' => 'Original video file not found on disk.',
                'error' => 'file_not_found',
            ];
        }

        // Check FFmpeg availability (early fail)
        $ffmpegCheck = $this->checkFfmpeg();
        if (!$ffmpegCheck['available']) {
            $this->logger->error($video, 'Cannot start HLS transcoding: FFmpeg not available', [
                'reason' => $reason,
                'ffmpeg_error' => $ffmpegCheck['message'],
            ]);

            $video->update([
                'processing_state' => Video::PROCESSING_FAILED,
                'processing_error' => 'FFmpeg not installed or not accessible',
                'processing_finished_at' => now(),
            ]);

            return [
                'success' => false,
                'message' => 'FFmpeg is not available. Please install FFmpeg.',
                'error' => 'ffmpeg_missing',
            ];
        }

        // Get queue info for logging
        $queueConnection = config('queue.default');
        $queueName = 'hls'; // Use dedicated HLS queue
        
        // Set state to QUEUED (not PROCESSING - that's the job's responsibility)
        $video->update([
            'processing_state' => Video::PROCESSING_QUEUED,
            'processing_progress' => null, // null = estimating, not 0
            'processing_error' => null,
            'hls_queued_at' => now(),
            'hls_started_at' => null, // Will be set when job actually starts
            'hls_last_heartbeat_at' => null,
            'processing_started_at' => now(), // Keep for backward compat
            'processing_finished_at' => null,
        ]);

        // Log the queue action with detailed context
        $this->logger->info($video, 'HLS transcoding job queued', [
            'reason' => $reason,
            'queue_connection' => $queueConnection,
            'queue_name' => $queueName,
            'is_sync' => $queueConnection === 'sync',
            'video_uuid' => $video->uuid,
            'original_path' => $video->original_path,
        ]);

        try {
            // Dispatch the job to dedicated HLS queue
            TranscodeToHlsJob::dispatch($video->id)->onQueue($queueName);

            Log::info("[HlsService] Dispatched TranscodeToHlsJob", [
                'video_id' => $video->id,
                'uuid' => $video->uuid,
                'reason' => $reason,
                'queue_connection' => $queueConnection,
                'queue_name' => $queueName,
            ]);

            return [
                'success' => true,
                'message' => $queueConnection === 'sync' 
                    ? 'HLS transcoding completed (sync mode).' 
                    : "HLS transcoding job queued on '{$queueName}' queue.",
                'queue_connection' => $queueConnection,
                'queue_name' => $queueName,
            ];

        } catch (\Exception $e) {
            // Dispatch failed - likely queue issue
            $this->logger->error($video, 'Failed to dispatch HLS transcoding job: ' . $e->getMessage(), [
                'reason' => $reason,
                'exception' => get_class($e),
            ]);

            $video->update([
                'processing_state' => Video::PROCESSING_FAILED,
                'processing_error' => 'Queue dispatch failed: ' . $e->getMessage(),
                'processing_finished_at' => now(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to queue HLS transcoding: ' . $e->getMessage(),
                'error' => 'dispatch_failed',
            ];
        }
    }

    /**
     * Check if a video is actively being processed (and not stale).
     */
    protected function isActivelyProcessing(Video $video): bool
    {
        // If queued and recent (< 2 min), consider active
        if ($video->processing_state === Video::PROCESSING_QUEUED) {
            if ($video->hls_queued_at && $video->hls_queued_at->diffInSeconds(now()) < 120) {
                return true;
            }
        }

        // If processing with recent heartbeat, consider active
        if ($video->processing_state === Video::PROCESSING_RUNNING) {
            if ($video->hls_last_heartbeat_at && $video->hls_last_heartbeat_at->diffInSeconds(now()) < 120) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if FFmpeg is available.
     */
    public function checkFfmpeg(): array
    {
        $paths = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/opt/homebrew/bin/ffmpeg',
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                // Get version
                $version = $this->getToolVersion($path);
                return [
                    'available' => true,
                    'path' => $path,
                    'version' => $version,
                    'message' => "FFmpeg found at {$path}" . ($version ? " ({$version})" : ''),
                ];
            }
        }

        // Try which command
        exec('which ffmpeg 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0 && !empty($output[0])) {
            $path = trim($output[0]);
            $version = $this->getToolVersion($path);
            return [
                'available' => true,
                'path' => $path,
                'version' => $version,
                'message' => "FFmpeg found at {$path}" . ($version ? " ({$version})" : ''),
            ];
        }

        return [
            'available' => false,
            'path' => null,
            'version' => null,
            'message' => 'FFmpeg not found in common paths or via which command',
        ];
    }

    /**
     * Check if FFprobe is available.
     */
    public function checkFfprobe(): array
    {
        $paths = [
            '/usr/bin/ffprobe',
            '/usr/local/bin/ffprobe',
            '/opt/homebrew/bin/ffprobe',
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                $version = $this->getToolVersion($path);
                return [
                    'available' => true,
                    'path' => $path,
                    'version' => $version,
                    'message' => "FFprobe found at {$path}" . ($version ? " ({$version})" : ''),
                ];
            }
        }

        exec('which ffprobe 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0 && !empty($output[0])) {
            $path = trim($output[0]);
            $version = $this->getToolVersion($path);
            return [
                'available' => true,
                'path' => $path,
                'version' => $version,
                'message' => "FFprobe found at {$path}" . ($version ? " ({$version})" : ''),
            ];
        }

        return [
            'available' => false,
            'path' => null,
            'version' => null,
            'message' => 'FFprobe not found',
        ];
    }

    /**
     * Get version string for ffmpeg/ffprobe.
     */
    protected function getToolVersion(string $path): ?string
    {
        exec("{$path} -version 2>&1 | head -1", $output, $returnCode);
        if ($returnCode === 0 && !empty($output[0])) {
            // Extract version from "ffmpeg version 4.4.2 ..."
            if (preg_match('/version\s+([\d\.]+)/', $output[0], $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Get HLS system health status for admin display.
     */
    public function getHealthStatus(): array
    {
        $queueConnection = config('queue.default');
        $ffmpegCheck = $this->checkFfmpeg();
        $ffprobeCheck = $this->checkFfprobe();

        // Get queue stats if using database queue
        $queueStats = $this->getQueueStats();

        return [
            'queue_connection' => $queueConnection,
            'queue_is_sync' => $queueConnection === 'sync',
            'ffmpeg_available' => $ffmpegCheck['available'],
            'ffmpeg_path' => $ffmpegCheck['path'],
            'ffmpeg_version' => $ffmpegCheck['version'] ?? null,
            'ffprobe_available' => $ffprobeCheck['available'],
            'ffprobe_path' => $ffprobeCheck['path'],
            'ffprobe_version' => $ffprobeCheck['version'] ?? null,
            'ready' => $ffmpegCheck['available'] && $ffprobeCheck['available'],
            'warnings' => $this->getWarnings($queueConnection, $ffmpegCheck, $ffprobeCheck),
            'queue_stats' => $queueStats,
        ];
    }

    /**
     * Get queue statistics (pending jobs, etc).
     */
    public function getQueueStats(): array
    {
        $connection = config('queue.default');
        
        $stats = [
            'connection' => $connection,
            'pending_hls_jobs' => 0,
            'pending_all_jobs' => 0,
            'videos_queued' => 0,
            'videos_processing' => 0,
            'videos_stuck' => 0,
        ];

        // Count videos in each state
        $stats['videos_queued'] = Video::where('processing_state', Video::PROCESSING_QUEUED)->count();
        $stats['videos_processing'] = Video::where('processing_state', Video::PROCESSING_RUNNING)->count();
        
        // Count stuck videos
        $stats['videos_stuck'] = Video::where(function ($q) {
            // Stuck in queue (> 2 min)
            $q->where(function ($sub) {
                $sub->where('processing_state', Video::PROCESSING_QUEUED)
                    ->where('hls_queued_at', '<', now()->subMinutes(2));
            })
            // OR stuck in processing (heartbeat stale > 2 min)
            ->orWhere(function ($sub) {
                $sub->where('processing_state', Video::PROCESSING_RUNNING)
                    ->where('hls_last_heartbeat_at', '<', now()->subMinutes(2));
            });
        })->count();

        // If using database queue, count pending jobs
        if ($connection === 'database') {
            try {
                $stats['pending_hls_jobs'] = DB::table('jobs')
                    ->where('queue', 'hls')
                    ->count();
                $stats['pending_all_jobs'] = DB::table('jobs')->count();
            } catch (\Exception $e) {
                // Jobs table might not exist
            }
        }

        return $stats;
    }

    /**
     * Detect and mark stuck HLS jobs.
     * 
     * @return array Summary of actions taken
     */
    public function detectAndMarkStuckJobs(): array
    {
        $results = [
            'stuck_in_queue' => 0,
            'stuck_in_processing' => 0,
            'marked_failed' => [],
        ];

        // Find videos stuck in queue (> 2 min without starting)
        $stuckQueued = Video::where('processing_state', Video::PROCESSING_QUEUED)
            ->where('hls_queued_at', '<', now()->subMinutes(2))
            ->get();

        foreach ($stuckQueued as $video) {
            $results['stuck_in_queue']++;
            
            $this->logger->error($video, 'HLS job stuck in queue - likely no queue worker running', [
                'queued_at' => $video->hls_queued_at?->toIso8601String(),
                'minutes_waiting' => $video->hls_queued_at?->diffInMinutes(now()),
            ]);

            $video->update([
                'processing_state' => Video::PROCESSING_FAILED,
                'processing_error' => 'Job stuck in queue for > 2 minutes. Check if queue worker is running: php artisan queue:work --queue=hls',
                'processing_finished_at' => now(),
            ]);

            $results['marked_failed'][] = $video->id;
        }

        // Find videos stuck in processing (heartbeat stale > 2 min)
        $stuckProcessing = Video::where('processing_state', Video::PROCESSING_RUNNING)
            ->where(function ($q) {
                $q->where('hls_last_heartbeat_at', '<', now()->subMinutes(2))
                  ->orWhereNull('hls_last_heartbeat_at');
            })
            ->where('hls_started_at', '<', now()->subMinutes(2))
            ->get();

        foreach ($stuckProcessing as $video) {
            $results['stuck_in_processing']++;

            $this->logger->error($video, 'HLS job appears hung - no heartbeat for > 2 minutes', [
                'started_at' => $video->hls_started_at?->toIso8601String(),
                'last_heartbeat' => $video->hls_last_heartbeat_at?->toIso8601String(),
            ]);

            $video->update([
                'processing_state' => Video::PROCESSING_FAILED,
                'processing_error' => 'Job hung (no heartbeat for > 2 min). FFmpeg may have crashed or input file may be corrupted.',
                'processing_finished_at' => now(),
            ]);

            $results['marked_failed'][] = $video->id;
        }

        return $results;
    }

    protected function getWarnings(string $queueConnection, array $ffmpegCheck, array $ffprobeCheck): array
    {
        $warnings = [];

        if (!$ffmpegCheck['available']) {
            $warnings[] = 'FFmpeg is not installed. HLS transcoding will fail.';
        }

        if (!$ffprobeCheck['available']) {
            $warnings[] = 'FFprobe is not installed. Video analysis will fail.';
        }

        if ($queueConnection === 'sync') {
            $warnings[] = 'Queue is set to "sync" mode. HLS jobs will block the request. Consider using a proper queue driver (database, redis) for production.';
        }

        // Check for stuck videos
        $stuckCount = Video::where(function ($q) {
            $q->where(function ($sub) {
                $sub->where('processing_state', Video::PROCESSING_QUEUED)
                    ->where('hls_queued_at', '<', now()->subMinutes(2));
            })
            ->orWhere(function ($sub) {
                $sub->where('processing_state', Video::PROCESSING_RUNNING)
                    ->where('hls_last_heartbeat_at', '<', now()->subMinutes(2));
            });
        })->count();

        if ($stuckCount > 0) {
            $warnings[] = "{$stuckCount} video(s) appear stuck. Check if queue worker is running.";
        }

        return $warnings;
    }

    /**
     * Check if a video can have HLS generated.
     */
    public function canGenerateHls(Video $video): array
    {
        $issues = [];

        if (!$video->original_path) {
            $issues[] = 'No original video file path set';
        } elseif (!Storage::disk('public')->exists($video->original_path)) {
            $issues[] = 'Original video file does not exist on disk';
        }

        if (in_array($video->processing_state, [Video::PROCESSING_QUEUED, Video::PROCESSING_RUNNING])) {
            $issues[] = 'HLS transcoding is already in progress';
        }

        $ffmpegCheck = $this->checkFfmpeg();
        if (!$ffmpegCheck['available']) {
            $issues[] = 'FFmpeg is not available';
        }

        return [
            'can_generate' => empty($issues),
            'issues' => $issues,
        ];
    }
}
