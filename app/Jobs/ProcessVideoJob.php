<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Models\Video;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Process Video Job - Fast metadata extraction and thumbnail generation.
 * 
 * This job performs lightweight operations only:
 * - Extract video duration using ffprobe
 * - Generate thumbnail image
 * - Optionally dispatch HLS transcoding job
 * 
 * Video playback uses direct MP4 streaming with HTTP Range support,
 * which is simpler and more reliable than HLS for most use cases.
 * HLS is generated asynchronously for adaptive bitrate streaming.
 */
class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     * Reduced from 2 hours since we no longer do HLS transcoding.
     */
    public int $timeout = 120; // 2 minutes max (thumbnail + duration extraction)

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    public function __construct(
        public Video $video
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        Log::info("Starting video metadata extraction", [
            'video_id' => $this->video->id,
            'uuid' => $this->video->uuid,
            'original_path' => $this->video->original_path,
        ]);

        try {
            // Mark processing_state as running (video is already published!)
            $this->video->update([
                'processing_state' => 'processing',
                'processing_error' => null,
            ]);

            // Get the full path to the video file
            $videoPath = Storage::disk('public')->path($this->video->original_path);

            if (!file_exists($videoPath)) {
                throw new \Exception("Video file not found: {$videoPath}");
            }

            $duration = null;
            $thumbnailPath = $this->video->thumbnail_path;

            // Check ffmpeg/ffprobe availability
            $ffmpeg = $this->findExecutable('ffmpeg');
            $ffprobe = $this->findExecutable('ffprobe');
            
            if ($ffprobe) {
                // Extract duration using ffprobe (fast operation)
                $duration = $this->extractDuration($videoPath);
                Log::info("Extracted duration", ['video_id' => $this->video->id, 'duration' => $duration]);
            } else {
                Log::warning("FFprobe not found, skipping duration extraction", ['video_id' => $this->video->id]);
            }

            if ($ffmpeg) {
                // Generate thumbnail only if not already exists
                if (!$thumbnailPath || !Storage::disk('public')->exists($thumbnailPath)) {
                    $thumbnailPath = $this->generateThumbnail($videoPath, $duration);
                    Log::info("Generated thumbnail", ['video_id' => $this->video->id, 'thumbnail_path' => $thumbnailPath]);
                }
            } else {
                Log::warning("FFmpeg not found, skipping thumbnail generation", ['video_id' => $this->video->id]);
            }

            // Update video record - no HLS, just metadata
            $updateData = [
                'processing_state' => 'ready',
                'processed_at' => now(),
                'processing_error' => null,
            ];

            if ($duration !== null) {
                $updateData['duration_seconds'] = $duration;
            }

            if ($thumbnailPath) {
                $updateData['thumbnail_path'] = $thumbnailPath;
            }

            $this->video->update($updateData);

            Log::info("Video processing completed (MP4 streaming ready)", [
                'video_id' => $this->video->id,
                'uuid' => $this->video->uuid,
                'duration' => $duration,
                'thumbnail' => $thumbnailPath,
            ]);

            // Optionally dispatch HLS transcoding based on settings
            $this->maybeDispatchHlsTranscoding();

        } catch (\Exception $e) {
            Log::error("Video processing failed (video still playable via MP4)", [
                'video_id' => $this->video->id,
                'uuid' => $this->video->uuid,
                'error' => $e->getMessage(),
            ]);

            // Mark processing as failed but video is still playable!
            $this->video->update([
                'processing_state' => 'failed',
                'processing_error' => $e->getMessage(),
                'processed_at' => now(),
            ]);

            // Don't re-throw - video is still published and playable
        }
    }

    /**
     * Extract video duration using ffprobe.
     */
    protected function extractDuration(string $videoPath): ?int
    {
        $ffprobe = $this->findExecutable('ffprobe');
        if (!$ffprobe) {
            Log::warning("FFprobe not found, cannot extract duration");
            return null;
        }

        $command = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellarg($ffprobe),
            escapeshellarg($videoPath)
        );

        $output = shell_exec($command);
        
        if ($output && is_numeric(trim($output))) {
            return (int) round((float) trim($output));
        }

        Log::warning("Could not extract duration", ['output' => $output]);
        return null;
    }

    /**
     * Generate a thumbnail from the video.
     */
    protected function generateThumbnail(string $videoPath, ?int $duration): ?string
    {
        $ffmpeg = $this->findExecutable('ffmpeg');
        if (!$ffmpeg) {
            Log::warning("FFmpeg not found, cannot generate thumbnail");
            return null;
        }

        $thumbnailPath = "videos/{$this->video->uuid}/thumb.jpg";
        $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);

        // Ensure directory exists
        $dir = dirname($thumbnailFullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Get position for thumbnail (at 25% of video or 10 seconds if duration unknown)
        $position = $duration ? max(1, (int) ($duration * 0.25)) : 10;
        
        // Cap position to avoid going past end of video
        if ($duration && $position >= $duration) {
            $position = max(1, (int) ($duration / 2));
        }

        $command = sprintf(
            '%s -y -i %s -ss %d -vframes 1 -vf "scale=640:-2" -q:v 2 %s 2>&1',
            escapeshellarg($ffmpeg),
            escapeshellarg($videoPath),
            $position,
            escapeshellarg($thumbnailFullPath)
        );

        $output = shell_exec($command);

        if (file_exists($thumbnailFullPath) && filesize($thumbnailFullPath) > 0) {
            return $thumbnailPath;
        }

        Log::warning("Thumbnail generation failed", ['command' => $command, 'output' => $output]);
        return null;
    }

    /**
     * Find the path to an executable.
     */
    protected function findExecutable(string $name): ?string
    {
        $paths = [
            "/usr/bin/{$name}",
            "/usr/local/bin/{$name}",
            "/opt/homebrew/bin/{$name}",
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Try which command
        $which = trim(shell_exec("which {$name} 2>/dev/null") ?? '');
        if ($which && file_exists($which)) {
            return $which;
        }

        return null;
    }

    /**
     * Handle a job failure.
     * Note: Video is still published and playable - this only affects optimization.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Video optimization job failed", [
            'video_id' => $this->video->id,
            'uuid' => $this->video->uuid,
            'error' => $exception->getMessage(),
        ]);

        $this->video->update([
            'processing_state' => 'failed',
            'processing_error' => $exception->getMessage(),
            'processed_at' => now(),
        ]);
    }

    /**
     * Optionally dispatch HLS transcoding based on global settings and video settings.
     */
    protected function maybeDispatchHlsTranscoding(): void
    {
        // Check if HLS processing is enabled globally
        $hlsEnabled = Setting::get('enable_hls_processing', true);
        $autoGenerateHls = Setting::get('auto_generate_hls', true);

        if (!$hlsEnabled || !$autoGenerateHls) {
            Log::info("HLS auto-generation disabled", [
                'video_id' => $this->video->id,
                'enable_hls_processing' => $hlsEnabled,
                'auto_generate_hls' => $autoGenerateHls,
            ]);
            return;
        }

        // Check if video has HLS enabled
        if (!$this->video->hls_enabled) {
            Log::info("HLS not enabled for this video", ['video_id' => $this->video->id]);
            return;
        }

        // Check if HLS is already ready
        if ($this->video->processing_state === Video::PROCESSING_READY && $this->video->hls_master_path) {
            Log::info("HLS already ready for this video", ['video_id' => $this->video->id]);
            return;
        }

        // Check if original file exists
        if (!$this->video->has_original) {
            Log::warning("Cannot dispatch HLS transcoding - no original file", ['video_id' => $this->video->id]);
            return;
        }

        Log::info("Dispatching HLS transcoding job", ['video_id' => $this->video->id]);

        // Update state to indicate HLS is pending
        $this->video->update([
            'processing_state' => Video::PROCESSING_PENDING,
        ]);

        TranscodeToHlsJob::dispatch($this->video);
    }
}
