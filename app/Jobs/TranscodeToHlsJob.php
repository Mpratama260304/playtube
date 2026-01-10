<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\VideoProcessingLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Transcode video to HLS format with multiple quality variants.
 * 
 * KEY IMPROVEMENTS:
 * - Sets processing_state to 'processing' ONLY when job actually starts (not when queued)
 * - Sends heartbeat every 5-10 seconds
 * - Logs progress at least every 20 seconds even if progress unchanged
 * - Robust ffmpeg -progress parsing with fallback
 * - Stuck detection via heartbeat timestamp
 */
class TranscodeToHlsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 7200; // 2 hours

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    protected ?VideoProcessingLogger $logger = null;
    protected ?Video $video = null;
    protected ?float $videoDuration = null;
    protected int $lastProgressPercent = -1;
    protected int $lastProgressUpdateTime = 0;
    protected int $lastHeartbeatTime = 0;
    protected int $lastLogTime = 0;
    protected ?string $ffmpegPath = null;
    protected ?string $ffprobePath = null;

    // Configuration
    protected const HEARTBEAT_INTERVAL = 5;      // Seconds between heartbeat updates
    protected const LOG_INTERVAL = 20;            // Seconds between "still processing" logs
    protected const PROGRESS_UPDATE_INTERVAL = 3; // Seconds between progress DB updates
    protected const STUCK_TIMEOUT = 60;           // Seconds without output before warning

    public function __construct(
        public int $videoId
    ) {}

    public function handle(VideoProcessingLogger $logger): void
    {
        $this->logger = $logger;
        $this->video = Video::find($this->videoId);

        if (!$this->video) {
            throw new \Exception("Video {$this->videoId} not found");
        }

        try {
            // CRITICAL: Mark as PROCESSING now that job has actually started
            $this->markJobStarted();
            
            // Validate prerequisites and log them
            $this->validateAndLogPrerequisites();
            
            // Get source video info via ffprobe
            $sourceInfo = $this->getSourceInfo();
            
            // Determine renditions based on source quality
            $renditions = $this->determineRenditions($sourceInfo);
            
            // Create HLS output directory
            $this->prepareOutputDirectory();
            
            // Generate HLS for each rendition with progress tracking
            $this->generateHls($renditions, $sourceInfo);
            
            // Create master playlist
            $this->createMasterPlaylist($renditions);
            
            // Mark as complete
            $this->markProcessingComplete();
            
        } catch (\Exception $e) {
            $this->handleFailure($e);
            throw $e;
        }
    }

    /**
     * Mark job as actually started (PROCESSING state).
     * This is when ffmpeg is about to run, not when job was queued.
     */
    protected function markJobStarted(): void
    {
        $now = now();
        $this->lastHeartbeatTime = time();
        $this->lastLogTime = time();
        
        $this->video->update([
            'processing_state' => Video::PROCESSING_RUNNING,
            'processing_progress' => 0,
            'hls_started_at' => $now,
            'hls_last_heartbeat_at' => $now,
            'processing_error' => null,
        ]);

        $this->logger->info($this->video, 'HLS transcoding job started', [
            'video_id' => $this->video->id,
            'video_uuid' => $this->video->uuid,
            'original_path' => $this->video->original_path,
            'queue_connection' => config('queue.default'),
            'job_attempt' => $this->attempts(),
        ]);
    }

    /**
     * Validate prerequisites and log detailed info for debugging.
     */
    protected function validateAndLogPrerequisites(): void
    {
        // Check and log FFmpeg
        $this->ffmpegPath = $this->findExecutable('ffmpeg');
        if (!$this->ffmpegPath) {
            $this->logger->error($this->video, 'FFmpeg not found - cannot proceed', [
                'searched_paths' => ['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/opt/homebrew/bin/ffmpeg'],
            ]);
            throw new \Exception('FFmpeg not found. Please install FFmpeg to enable HLS transcoding.');
        }

        // Log FFmpeg version
        $ffmpegVersion = $this->getToolVersion($this->ffmpegPath);
        $this->logger->info($this->video, 'FFmpeg found', [
            'path' => $this->ffmpegPath,
            'version' => $ffmpegVersion ?? 'unknown',
        ]);

        // Check and log FFprobe
        $this->ffprobePath = $this->findExecutable('ffprobe');
        if (!$this->ffprobePath) {
            $this->logger->error($this->video, 'FFprobe not found - cannot proceed', []);
            throw new \Exception('FFprobe not found. Please install FFprobe to enable HLS transcoding.');
        }

        $ffprobeVersion = $this->getToolVersion($this->ffprobePath);
        $this->logger->info($this->video, 'FFprobe found', [
            'path' => $this->ffprobePath,
            'version' => $ffprobeVersion ?? 'unknown',
        ]);

        // Check source file
        $sourcePath = $this->video->normalized_original_path;
        if (!$sourcePath || !Storage::disk('public')->exists($sourcePath)) {
            $this->logger->error($this->video, 'Source video file not found', [
                'path' => $sourcePath,
            ]);
            throw new \Exception('Source video file not found: ' . ($sourcePath ?? 'null'));
        }

        // Log source file info
        $fullPath = Storage::disk('public')->path($sourcePath);
        $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;
        
        $this->logger->info($this->video, 'Source file validated', [
            'path' => $sourcePath,
            'size_bytes' => $fileSize,
            'size_mb' => round($fileSize / 1024 / 1024, 2),
        ]);

        $this->updateHeartbeat();
    }

    /**
     * Get source video information via FFprobe.
     */
    protected function getSourceInfo(): array
    {
        $sourcePath = Storage::disk('public')->path($this->video->normalized_original_path);

        // Get video stream info
        $command = sprintf(
            '%s -v error -select_streams v:0 -show_entries stream=width,height,bit_rate,duration -of json %s 2>&1',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($sourcePath)
        );

        exec($command, $output, $returnCode);
        $jsonOutput = implode('', $output);
        $data = json_decode($jsonOutput, true);

        $stream = $data['streams'][0] ?? [];
        
        $width = (int) ($stream['width'] ?? 1280);
        $height = (int) ($stream['height'] ?? 720);
        $bitrate = (int) ($stream['bit_rate'] ?? 2000000);
        $duration = (float) ($stream['duration'] ?? $this->video->duration_seconds ?? 0);

        $this->videoDuration = $duration;

        // Log ffprobe results
        $this->logger->info($this->video, 'FFprobe analysis complete', [
            'width' => $width,
            'height' => $height,
            'bitrate_bps' => $bitrate,
            'duration_seconds' => $duration,
            'duration_formatted' => gmdate('H:i:s', (int)$duration),
        ]);

        // Warn if duration unknown
        if ($duration <= 0) {
            $this->logger->warn($this->video, 'Video duration unknown - progress will show as estimating', [
                'fallback_mode' => 'heartbeat_only',
            ]);
        }

        $this->updateHeartbeat();

        return [
            'width' => $width,
            'height' => $height,
            'bitrate' => $bitrate,
            'duration' => $duration,
        ];
    }

    /**
     * Determine which renditions to create based on source quality.
     */
    protected function determineRenditions(array $sourceInfo): array
    {
        $sourceHeight = $sourceInfo['height'];
        $renditions = [];

        // Always include 360p as baseline
        $renditions[] = [
            'name' => '360p',
            'height' => 360,
            'width' => 640,
            'bitrate' => '800k',
            'audioBitrate' => '96k',
            'folder' => 'v360',
        ];

        if ($sourceHeight >= 480) {
            $renditions[] = [
                'name' => '480p',
                'height' => 480,
                'width' => 854,
                'bitrate' => '1400k',
                'audioBitrate' => '128k',
                'folder' => 'v480',
            ];
        }

        if ($sourceHeight >= 720) {
            $renditions[] = [
                'name' => '720p',
                'height' => 720,
                'width' => 1280,
                'bitrate' => '2800k',
                'audioBitrate' => '128k',
                'folder' => 'v720',
            ];
        }

        if ($sourceHeight >= 1080) {
            $renditions[] = [
                'name' => '1080p',
                'height' => 1080,
                'width' => 1920,
                'bitrate' => '5000k',
                'audioBitrate' => '192k',
                'folder' => 'v1080',
            ];
        }

        $this->logger->info($this->video, 'Renditions selected', [
            'source_height' => $sourceHeight,
            'renditions' => array_column($renditions, 'name'),
            'rendition_count' => count($renditions),
        ]);

        return $renditions;
    }

    /**
     * Prepare output directory.
     */
    protected function prepareOutputDirectory(): void
    {
        $hlsDir = $this->video->hls_directory;
        $fullPath = Storage::disk('public')->path($hlsDir);

        if (File::isDirectory($fullPath)) {
            File::deleteDirectory($fullPath);
        }

        File::makeDirectory($fullPath, 0755, true);

        $this->logger->info($this->video, 'HLS output directory prepared', [
            'path' => $hlsDir,
        ]);

        $this->updateHeartbeat();
    }

    /**
     * Generate HLS for all renditions with robust progress tracking.
     */
    protected function generateHls(array $renditions, array $sourceInfo): void
    {
        $sourcePath = Storage::disk('public')->path($this->video->normalized_original_path);
        $hlsBasePath = Storage::disk('public')->path($this->video->hls_directory);

        $totalRenditions = count($renditions);
        $completedRenditions = 0;

        foreach ($renditions as $rendition) {
            $renditionDir = "{$hlsBasePath}/{$rendition['folder']}";
            File::makeDirectory($renditionDir, 0755, true);

            $playlistPath = "{$renditionDir}/index.m3u8";
            $segmentPath = "{$renditionDir}/seg_%05d.ts";

            $this->logger->info($this->video, "Starting {$rendition['name']} encoding", [
                'resolution' => "{$rendition['width']}x{$rendition['height']}",
                'bitrate' => $rendition['bitrate'],
                'rendition' => ($completedRenditions + 1) . "/{$totalRenditions}",
            ]);

            // Build FFmpeg command with -progress pipe:1 for parsing
            $command = sprintf(
                '%s -y -i %s ' .
                '-vf "scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2" ' .
                '-c:v libx264 -preset fast -crf 23 -b:v %s -maxrate %s -bufsize %s ' .
                '-c:a aac -b:a %s -ar 44100 ' .
                '-f hls -hls_time 6 -hls_playlist_type vod ' .
                '-hls_flags independent_segments ' .
                '-hls_segment_filename %s ' .
                '-progress pipe:1 -nostats ' .
                '%s 2>&1',
                escapeshellarg($this->ffmpegPath),
                escapeshellarg($sourcePath),
                $rendition['width'],
                $rendition['height'],
                $rendition['width'],
                $rendition['height'],
                $rendition['bitrate'],
                $rendition['bitrate'],
                $this->calculateBufferSize($rendition['bitrate']),
                $rendition['audioBitrate'],
                escapeshellarg($segmentPath),
                escapeshellarg($playlistPath)
            );

            // Execute with progress tracking
            $this->executeWithProgressTracking($command, $completedRenditions, $totalRenditions, $rendition['name']);

            $completedRenditions++;

            // Verify output
            if (!File::exists($playlistPath)) {
                throw new \Exception("Failed to create {$rendition['name']} playlist at {$playlistPath}");
            }

            $this->logger->info($this->video, "Completed {$rendition['name']} encoding", [
                'playlist' => "{$rendition['folder']}/index.m3u8",
            ]);
        }
    }

    /**
     * Execute FFmpeg with robust progress tracking and heartbeat.
     */
    protected function executeWithProgressTracking(string $command, int $completedRenditions, int $totalRenditions, string $renditionName): void
    {
        $process = popen($command, 'r');
        if (!$process) {
            throw new \Exception('Failed to start FFmpeg process');
        }

        $renditionBaseProgress = ($completedRenditions / $totalRenditions) * 100;
        $renditionProgressRange = 100 / $totalRenditions;
        $lastOutputTime = time();
        $currentOutTime = 0;

        // Non-blocking read loop
        stream_set_blocking($process, false);

        while (!feof($process)) {
            $line = fgets($process);
            
            if ($line !== false) {
                $lastOutputTime = time();
                $line = trim($line);

                // Parse out_time_ms from ffmpeg -progress output
                if (preg_match('/out_time_ms=(\d+)/', $line, $matches)) {
                    $currentTimeMs = (int) $matches[1];
                    $currentOutTime = $currentTimeMs / 1000000; // Convert to seconds
                }

                // Parse out_time=HH:MM:SS.ms format as fallback
                if (preg_match('/out_time=(\d{2}):(\d{2}):(\d{2})\.(\d+)/', $line, $matches)) {
                    $currentOutTime = ($matches[1] * 3600) + ($matches[2] * 60) + $matches[3] + ($matches[4] / 1000000);
                }

                // Calculate and update progress
                if ($this->videoDuration > 0 && $currentOutTime > 0) {
                    $renditionProgress = min(100, ($currentOutTime / $this->videoDuration) * 100);
                    $overallProgress = (int) ($renditionBaseProgress + ($renditionProgress / 100) * $renditionProgressRange);
                    $overallProgress = min(99, max(0, $overallProgress));
                    
                    $this->updateProgress($overallProgress, $currentOutTime);
                }

                // Check for errors in output
                if (stripos($line, 'error') !== false || stripos($line, 'invalid') !== false) {
                    $this->logger->warn($this->video, "FFmpeg warning: {$line}", [
                        'rendition' => $renditionName,
                    ]);
                }
            }

            // Always update heartbeat regardless of output
            $this->updateHeartbeat();

            // Log "still processing" periodically
            $this->logStillProcessing($renditionName, $currentOutTime);

            // Check for stuck (no output for too long)
            if (time() - $lastOutputTime > self::STUCK_TIMEOUT) {
                $this->logger->warn($this->video, 'FFmpeg appears slow - no output for ' . self::STUCK_TIMEOUT . 's', [
                    'rendition' => $renditionName,
                    'last_out_time' => $currentOutTime,
                ]);
                $lastOutputTime = time(); // Reset to avoid spamming
            }

            // Small sleep to prevent CPU spinning
            usleep(100000); // 100ms
        }

        $returnCode = pclose($process);
        if ($returnCode !== 0) {
            throw new \Exception("FFmpeg process failed for {$renditionName} with exit code: {$returnCode}");
        }
    }

    /**
     * Update progress in database (throttled).
     */
    protected function updateProgress(int $progress, float $currentTime = 0): void
    {
        $now = time();
        
        // Update if: progress changed significantly OR time interval passed
        $shouldUpdate = 
            ($progress - $this->lastProgressPercent >= 1) ||
            ($now - $this->lastProgressUpdateTime >= self::PROGRESS_UPDATE_INTERVAL);

        if (!$shouldUpdate) {
            return;
        }

        $this->video->update([
            'processing_progress' => $progress,
            'hls_last_heartbeat_at' => now(),
        ]);

        $this->lastProgressPercent = $progress;
        $this->lastProgressUpdateTime = $now;

        // Log at 10% intervals
        if ($progress % 10 === 0 && $progress > 0) {
            $this->logger->progress($this->video, $progress, '', 'hls');
        }
    }

    /**
     * Update heartbeat timestamp (called frequently).
     */
    protected function updateHeartbeat(): void
    {
        $now = time();
        
        if ($now - $this->lastHeartbeatTime >= self::HEARTBEAT_INTERVAL) {
            $this->video->update([
                'hls_last_heartbeat_at' => now(),
            ]);
            $this->lastHeartbeatTime = $now;
        }
    }

    /**
     * Log "still processing" message periodically.
     */
    protected function logStillProcessing(string $stage, float $currentTime): void
    {
        $now = time();
        
        if ($now - $this->lastLogTime >= self::LOG_INTERVAL) {
            $elapsed = $this->video->hls_started_at 
                ? now()->diffInSeconds($this->video->hls_started_at) 
                : 0;

            $this->logger->info($this->video, 'Still processing', [
                'stage' => $stage,
                'elapsed_seconds' => $elapsed,
                'current_out_time' => round($currentTime, 2),
                'progress_percent' => $this->lastProgressPercent,
            ]);
            
            $this->lastLogTime = $now;
        }
    }

    /**
     * Create master playlist.
     */
    protected function createMasterPlaylist(array $renditions): void
    {
        $hlsBasePath = Storage::disk('public')->path($this->video->hls_directory);
        $masterPath = "{$hlsBasePath}/master.m3u8";

        $content = "#EXTM3U\n#EXT-X-VERSION:3\n\n";

        foreach ($renditions as $rendition) {
            $bandwidth = $this->parseBitrate($rendition['bitrate']) + $this->parseBitrate($rendition['audioBitrate']);
            $content .= sprintf(
                "#EXT-X-STREAM-INF:BANDWIDTH=%d,RESOLUTION=%dx%d,NAME=\"%s\"\n%s/index.m3u8\n\n",
                $bandwidth,
                $rendition['width'],
                $rendition['height'],
                $rendition['name'],
                $rendition['folder']
            );
        }

        File::put($masterPath, $content);

        $this->logger->info($this->video, 'Master playlist created', [
            'renditions' => count($renditions),
            'path' => 'master.m3u8',
        ]);
    }

    /**
     * Mark processing as complete.
     */
    protected function markProcessingComplete(): void
    {
        $hlsMasterPath = $this->video->hls_directory . '/master.m3u8';

        $duration = $this->video->hls_started_at 
            ? now()->diffInSeconds($this->video->hls_started_at) 
            : 0;

        $this->video->update([
            'hls_master_path' => $hlsMasterPath,
            'processing_state' => Video::PROCESSING_READY,
            'processing_progress' => 100,
            'processing_finished_at' => now(),
            'processing_error' => null,
        ]);

        $this->logger->info($this->video, 'HLS transcoding completed successfully', [
            'duration_seconds' => $duration,
            'master_path' => $hlsMasterPath,
        ]);

        // Cleanup old logs
        $this->logger->cleanup($this->video, 200, 'hls');
    }

    /**
     * Handle job failure.
     */
    protected function handleFailure(\Exception $e): void
    {
        $this->video->update([
            'processing_state' => Video::PROCESSING_FAILED,
            'processing_error' => $e->getMessage(),
            'processing_finished_at' => now(),
        ]);

        $this->logger->error($this->video, 'HLS transcoding failed: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'trace' => substr($e->getTraceAsString(), 0, 1000),
        ]);
    }

    /**
     * Find executable path.
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

        exec("which {$name} 2>/dev/null", $output, $returnCode);
        if ($returnCode === 0 && !empty($output[0])) {
            return trim($output[0]);
        }

        return null;
    }

    /**
     * Get tool version.
     */
    protected function getToolVersion(string $path): ?string
    {
        exec("{$path} -version 2>&1 | head -1", $output, $returnCode);
        if ($returnCode === 0 && !empty($output[0])) {
            if (preg_match('/version\s+([\d\.]+)/', $output[0], $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Parse bitrate string to integer.
     */
    protected function parseBitrate(string $bitrate): int
    {
        $value = (int) preg_replace('/[^0-9]/', '', $bitrate);
        if (str_contains(strtolower($bitrate), 'k')) {
            return $value * 1000;
        }
        if (str_contains(strtolower($bitrate), 'm')) {
            return $value * 1000000;
        }
        return $value;
    }

    /**
     * Calculate buffer size.
     */
    protected function calculateBufferSize(string $bitrate): string
    {
        $value = $this->parseBitrate($bitrate);
        return ($value * 2) . '';
    }

    /**
     * Handle job failure (Laravel hook).
     */
    public function failed(\Throwable $exception): void
    {
        $video = Video::find($this->videoId);
        if ($video) {
            $video->update([
                'processing_state' => Video::PROCESSING_FAILED,
                'processing_error' => $exception->getMessage(),
                'processing_finished_at' => now(),
            ]);
        }
    }
}
