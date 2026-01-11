<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * GenerateHlsSegmentsJob - Creates HLS segments for adaptive streaming
 * 
 * This job creates properly segmented HLS content that works with the
 * Go Video Server for optimal adaptive bitrate streaming.
 */
class GenerateHlsSegmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 3600; // 1 hour
    public int $maxExceptions = 1;

    protected Video $video;
    protected array $qualities;

    /**
     * Quality presets for HLS generation
     */
    protected array $qualityPresets = [
        '360p' => [
            'width' => 640,
            'height' => 360,
            'bitrate' => '800k',
            'maxrate' => '856k',
            'bufsize' => '1200k',
            'audio_bitrate' => '96k',
            'crf' => 28,
        ],
        '480p' => [
            'width' => 854,
            'height' => 480,
            'bitrate' => '1400k',
            'maxrate' => '1498k',
            'bufsize' => '2100k',
            'audio_bitrate' => '128k',
            'crf' => 26,
        ],
        '720p' => [
            'width' => 1280,
            'height' => 720,
            'bitrate' => '2500k',
            'maxrate' => '2675k',
            'bufsize' => '3750k',
            'audio_bitrate' => '128k',
            'crf' => 24,
        ],
        '1080p' => [
            'width' => 1920,
            'height' => 1080,
            'bitrate' => '5000k',
            'maxrate' => '5350k',
            'bufsize' => '7500k',
            'audio_bitrate' => '192k',
            'crf' => 22,
        ],
    ];

    public function __construct(Video $video, array $qualities = ['360p', '480p', '720p', '1080p'])
    {
        $this->video = $video;
        $this->qualities = $qualities;
        $this->onQueue('hls');
    }

    public function handle(): void
    {
        Log::info("Starting HLS generation for video: {$this->video->uuid}");

        try {
            // Get source video path
            $sourcePath = $this->getSourcePath();
            if (!$sourcePath || !file_exists($sourcePath)) {
                throw new \Exception("Source video not found: {$sourcePath}");
            }

            // Get video dimensions
            $dimensions = $this->getVideoDimensions($sourcePath);
            
            // Create HLS output directory
            $hlsBasePath = storage_path("app/private/hls/{$this->video->uuid}");
            if (!is_dir($hlsBasePath)) {
                mkdir($hlsBasePath, 0755, true);
            }

            // Determine which qualities to generate based on source resolution
            $applicableQualities = $this->getApplicableQualities($dimensions);

            // Generate HLS for each quality
            foreach ($applicableQualities as $quality) {
                $this->generateQualityHls($sourcePath, $hlsBasePath, $quality);
            }

            // Generate master playlist
            $this->generateMasterPlaylist($hlsBasePath, $applicableQualities);

            // Update video record
            $this->video->update([
                'hls_status' => 'ready',
                'hls_master_path' => "hls/{$this->video->uuid}/master.m3u8",
                'hls_qualities' => $applicableQualities,
            ]);

            Log::info("HLS generation completed for video: {$this->video->uuid}");

        } catch (\Exception $e) {
            Log::error("HLS generation failed: {$e->getMessage()}", [
                'video_uuid' => $this->video->uuid,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->video->update(['hls_status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Get source video path
     */
    protected function getSourcePath(): ?string
    {
        $basePath = storage_path("app/private/videos/{$this->video->uuid}");
        
        // Priority: stream.mp4 > original.mp4 > video.mp4
        $candidates = [
            "{$basePath}/stream.mp4",
            "{$basePath}/original.mp4",
            "{$basePath}/video.mp4",
            storage_path("app/private/videos/{$this->video->uuid}.mp4"),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Get video dimensions using ffprobe
     */
    protected function getVideoDimensions(string $path): array
    {
        $process = new Process([
            'ffprobe', '-v', 'error',
            '-select_streams', 'v:0',
            '-show_entries', 'stream=width,height',
            '-of', 'csv=p=0',
            $path
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            return ['width' => 1920, 'height' => 1080]; // Default
        }

        $output = trim($process->getOutput());
        $parts = explode(',', $output);

        return [
            'width' => (int) ($parts[0] ?? 1920),
            'height' => (int) ($parts[1] ?? 1080),
        ];
    }

    /**
     * Determine applicable qualities based on source resolution
     */
    protected function getApplicableQualities(array $dimensions): array
    {
        $sourceHeight = $dimensions['height'];
        $applicable = [];

        foreach ($this->qualities as $quality) {
            $preset = $this->qualityPresets[$quality] ?? null;
            if ($preset && $preset['height'] <= $sourceHeight) {
                $applicable[] = $quality;
            }
        }

        // Always include at least the lowest quality
        if (empty($applicable) && isset($this->qualityPresets['360p'])) {
            $applicable[] = '360p';
        }

        return $applicable;
    }

    /**
     * Generate HLS for a specific quality
     */
    protected function generateQualityHls(string $sourcePath, string $hlsBasePath, string $quality): void
    {
        $preset = $this->qualityPresets[$quality];
        $outputDir = "{$hlsBasePath}/{$quality}";

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        Log::info("Generating HLS {$quality} for video: {$this->video->uuid}");

        // FFmpeg command for HLS generation with fMP4 segments
        $command = [
            'ffmpeg', '-y',
            '-i', $sourcePath,
            
            // Video encoding
            '-c:v', 'libx264',
            '-preset', 'medium',
            '-crf', (string) $preset['crf'],
            '-maxrate', $preset['maxrate'],
            '-bufsize', $preset['bufsize'],
            '-vf', "scale={$preset['width']}:{$preset['height']}:force_original_aspect_ratio=decrease,pad={$preset['width']}:{$preset['height']}:(ow-iw)/2:(oh-ih)/2",
            '-profile:v', 'high',
            '-level', '4.1',
            '-pix_fmt', 'yuv420p',
            
            // Audio encoding
            '-c:a', 'aac',
            '-b:a', $preset['audio_bitrate'],
            '-ar', '48000',
            '-ac', '2',
            
            // HLS output settings
            '-f', 'hls',
            '-hls_time', '6',
            '-hls_list_size', '0',
            '-hls_flags', 'independent_segments',
            '-hls_segment_type', 'mpegts',
            '-hls_segment_filename', "{$outputDir}/segment%04d.ts",
            '-hls_playlist_type', 'vod',
            '-master_pl_name', 'master.m3u8',
            
            "{$outputDir}/playlist.m3u8"
        ];

        $process = new Process($command);
        $process->setTimeout(1800); // 30 minutes per quality
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception("FFmpeg HLS generation failed for {$quality}: " . $process->getErrorOutput());
        }

        Log::info("HLS {$quality} generation completed for video: {$this->video->uuid}");
    }

    /**
     * Generate master playlist
     */
    protected function generateMasterPlaylist(string $hlsBasePath, array $qualities): void
    {
        $content = "#EXTM3U\n";
        $content .= "#EXT-X-VERSION:3\n";

        foreach ($qualities as $quality) {
            $preset = $this->qualityPresets[$quality];
            $bandwidth = $this->parseBitrate($preset['bitrate']) + $this->parseBitrate($preset['audio_bitrate']);
            
            $content .= "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$preset['width']}x{$preset['height']},NAME=\"{$quality}\"\n";
            $content .= "{$quality}/playlist.m3u8\n";
        }

        file_put_contents("{$hlsBasePath}/master.m3u8", $content);
    }

    /**
     * Parse bitrate string to integer
     */
    protected function parseBitrate(string $bitrate): int
    {
        $value = (float) preg_replace('/[^0-9.]/', '', $bitrate);
        if (stripos($bitrate, 'k') !== false) {
            return (int) ($value * 1000);
        } elseif (stripos($bitrate, 'm') !== false) {
            return (int) ($value * 1000000);
        }
        return (int) $value;
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("GenerateHlsSegmentsJob failed permanently", [
            'video_uuid' => $this->video->uuid,
            'error' => $exception->getMessage(),
        ]);

        $this->video->update(['hls_status' => 'failed']);
    }
}
