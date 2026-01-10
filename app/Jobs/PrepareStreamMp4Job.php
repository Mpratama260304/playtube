<?php

namespace App\Jobs;

use App\Models\Video;
use App\Models\VideoProcessingLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Prepare fast-start MP4 for instant playback
 * 
 * Priority: HIGH (this enables playback ASAP)
 * Creates a streamable H.264+AAC MP4 with moov atom at start
 */
class PrepareStreamMp4Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 minutes
    public int $tries = 2;
    public int $backoff = 60;
    public bool $deleteWhenMissingModels = true;

    protected Video $video;

    public function __construct(Video $video)
    {
        $this->video = $video;
        $this->onQueue('high'); // High priority
    }

    public function handle(): void
    {
        $log = VideoProcessingLog::create([
            'video_id' => $this->video->id,
            'job_type' => 'prepare_stream',
            'status' => 'processing',
            'message' => 'Starting stream MP4 preparation',
            'started_at' => now(),
        ]);

        try {
            $this->video->update([
                'processing_state' => Video::PROCESSING_RUNNING,
                'processing_progress' => 0,
                'processing_started_at' => now(),
            ]);

            // Check if original file exists
            if (!$this->video->original_path || !Storage::disk('public')->exists($this->video->original_path)) {
                throw new \Exception('Original video file not found');
            }

            $inputPath = Storage::disk('public')->path($this->video->original_path);
            
            // Probe video to get info
            $probeData = $this->probeVideo($inputPath);
            $log->updateProgress(10, 'Video probed successfully');

            // Create output directory
            $outputDir = "videos/{$this->video->uuid}";
            Storage::disk('public')->makeDirectory($outputDir);
            
            $outputFilename = 'stream.mp4';
            $outputRelative = "{$outputDir}/{$outputFilename}";
            $outputPath = Storage::disk('public')->path($outputRelative);

            // Build ffmpeg command for fast streamable MP4
            $command = $this->buildFfmpegCommand($inputPath, $outputPath, $probeData);
            
            $log->updateProgress(20, 'Starting ffmpeg transcoding');
            
            // Execute ffmpeg with progress tracking
            $this->executeFfmpeg($command, $log, $probeData['duration'] ?? 0);

            // Verify output exists
            if (!file_exists($outputPath)) {
                throw new \Exception('Output file was not created');
            }

            $outputSize = filesize($outputPath);
            $log->updateProgress(95, 'Verifying output file');

            // Update video record
            $this->video->update([
                'stream_path' => $outputRelative,
                'stream_ready' => true,
                'duration_seconds' => round($probeData['duration'] ?? 0),
                'processing_state' => Video::PROCESSING_READY,
                'processing_progress' => 100,
                'processing_finished_at' => now(),
            ]);

            $log->markAsCompleted('Stream MP4 prepared successfully', [
                'output_path' => $outputRelative,
                'output_size' => $outputSize,
                'duration' => $probeData['duration'] ?? 0,
                'codec' => 'H.264/AAC',
            ]);

            Log::info("PrepareStreamMp4Job completed for video {$this->video->id}", [
                'output_path' => $outputRelative,
                'size' => $outputSize,
            ]);

            // Dispatch renditions job with lower priority
            dispatch(new BuildRenditionsJob($this->video))->onQueue('default');

        } catch (\Exception $e) {
            $error = "Failed to prepare stream MP4: {$e->getMessage()}";
            
            $this->video->update([
                'processing_state' => Video::PROCESSING_FAILED,
                'processing_error' => $error,
                'processing_finished_at' => now(),
            ]);

            $log->markAsFailed($error, ['exception' => $e->getMessage()]);
            
            Log::error("PrepareStreamMp4Job failed for video {$this->video->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function probeVideo(string $path): array
    {
        $cmd = sprintf(
            'ffprobe -v quiet -print_format json -show_format -show_streams %s',
            escapeshellarg($path)
        );

        $output = shell_exec($cmd);
        $data = json_decode($output, true);

        if (!$data) {
            throw new \Exception('Failed to probe video file');
        }

        $duration = (float)($data['format']['duration'] ?? 0);
        $videoStream = collect($data['streams'] ?? [])->firstWhere('codec_type', 'video');

        return [
            'duration' => $duration,
            'width' => $videoStream['width'] ?? null,
            'height' => $videoStream['height'] ?? null,
            'codec' => $videoStream['codec_name'] ?? null,
            'bitrate' => (int)($data['format']['bit_rate'] ?? 0),
        ];
    }

    protected function buildFfmpegCommand(string $input, string $output, array $probeData): string
    {
        // Use veryfast preset for quick processing
        // CRF 23 is good balance of quality and size
        // movflags +faststart ensures moov atom at beginning
        $parts = [
            'ffmpeg',
            '-y', // Overwrite output
            '-i', escapeshellarg($input),
            '-c:v', 'libx264',
            '-preset', 'veryfast',
            '-crf', '23',
            '-maxrate', '5000k', // Reasonable max for 720p-1080p
            '-bufsize', '7500k',
            '-g', '48', // GOP size for seekability
            '-keyint_min', '48',
            '-sc_threshold', '0',
            '-c:a', 'aac',
            '-b:a', '128k',
            '-movflags', '+faststart', // CRITICAL: puts moov atom at start
            '-f', 'mp4',
            escapeshellarg($output),
            '2>&1', // Capture stderr for progress
        ];

        return implode(' ', $parts);
    }

    protected function executeFfmpeg(string $command, VideoProcessingLog $log, float $duration): void
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);
        
        if (!is_resource($process)) {
            throw new \Exception('Failed to start ffmpeg process');
        }

        fclose($pipes[0]);
        
        $output = '';
        $lastUpdate = time();
        
        while (!feof($pipes[1]) || !feof($pipes[2])) {
            $stdout = fgets($pipes[1]);
            $stderr = fgets($pipes[2]);
            
            if ($stdout !== false) $output .= $stdout;
            if ($stderr !== false) $output .= $stderr;

            // Parse progress from ffmpeg output
            if ($duration > 0 && preg_match('/time=(\d{2}):(\d{2}):(\d{2})/', $output, $matches)) {
                $currentSeconds = ($matches[1] * 3600) + ($matches[2] * 60) + $matches[3];
                $progress = min(90, (int)(($currentSeconds / $duration) * 100));
                
                // Update every 5 seconds
                if (time() - $lastUpdate >= 5) {
                    $progress = max(20, $progress); // At least 20% when started
                    $log->updateProgress($progress, "Transcoding: {$progress}%");
                    $lastUpdate = time();
                }
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            throw new \Exception("ffmpeg failed with code {$returnCode}. Output: " . substr($output, -500));
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->video->update([
            'processing_state' => Video::PROCESSING_FAILED,
            'processing_error' => $exception->getMessage(),
            'processing_finished_at' => now(),
        ]);

        Log::error("PrepareStreamMp4Job failed permanently for video {$this->video->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
