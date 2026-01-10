<?php

namespace App\Jobs;

use App\Models\Video;
use App\Models\VideoProcessingLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Build multiple quality renditions (360p, 480p, 720p, 1080p)
 * 
 * Priority: NORMAL (runs after stream MP4 is ready)
 * Creates adaptive quality options for users
 */
class BuildRenditionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 60 minutes
    public int $tries = 2;
    public int $backoff = 120;
    public bool $deleteWhenMissingModels = true;

    protected Video $video;

    // Quality ladder: name => [height, maxrate_kbps, bufsize_kbps, audio_bitrate]
    protected array $qualities = [
        '360' => ['height' => 360, 'maxrate' => 800, 'bufsize' => 1200, 'audio' => 96],
        '480' => ['height' => 480, 'maxrate' => 1400, 'bufsize' => 2100, 'audio' => 128],
        '720' => ['height' => 720, 'maxrate' => 2500, 'bufsize' => 3750, 'audio' => 128],
        '1080' => ['height' => 1080, 'maxrate' => 5000, 'bufsize' => 7500, 'audio' => 192],
    ];

    public function __construct(Video $video)
    {
        $this->video = $video;
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $log = VideoProcessingLog::create([
            'video_id' => $this->video->id,
            'job_type' => 'build_renditions',
            'status' => 'processing',
            'message' => 'Starting renditions build',
            'started_at' => now(),
        ]);

        try {
            // Use stream_path as input if available, otherwise original
            $inputPath = null;
            
            if ($this->video->stream_path && Storage::disk('public')->exists($this->video->stream_path)) {
                $inputPath = Storage::disk('public')->path($this->video->stream_path);
            } elseif ($this->video->original_path && Storage::disk('public')->exists($this->video->original_path)) {
                $inputPath = Storage::disk('public')->path($this->video->original_path);
            }

            if (!$inputPath) {
                throw new \Exception('No source video file found for renditions');
            }

            // Probe source video
            $probeData = $this->probeVideo($inputPath);
            $sourceHeight = $probeData['height'] ?? 0;
            
            $log->updateProgress(5, "Source resolution: {$sourceHeight}p");

            // Determine which qualities to generate (don't upscale)
            $targetQualities = $this->determineTargetQualities($sourceHeight);
            
            if (empty($targetQualities)) {
                $log->markAsCompleted('No renditions needed (source is too small)', [
                    'source_height' => $sourceHeight,
                ]);
                return;
            }

            $renditions = $this->video->renditions ?? [];
            $totalQualities = count($targetQualities);
            $completed = 0;

            // Generate each quality
            foreach ($targetQualities as $qualityName) {
                $qualityConfig = $this->qualities[$qualityName];
                
                $log->updateProgress(
                    10 + (int)(($completed / $totalQualities) * 80),
                    "Building {$qualityName}p rendition"
                );

                $renditionInfo = $this->createRendition(
                    $inputPath,
                    $qualityName,
                    $qualityConfig,
                    $probeData
                );

                $renditions[$qualityName] = $renditionInfo;
                $completed++;

                // Save progress incrementally
                $this->video->update(['renditions' => $renditions]);
                
                Log::info("Rendition {$qualityName}p created for video {$this->video->id}", $renditionInfo);
            }

            $log->markAsCompleted("All renditions built successfully", [
                'renditions' => array_keys($renditions),
                'count' => count($renditions),
            ]);

            Log::info("BuildRenditionsJob completed for video {$this->video->id}", [
                'renditions' => array_keys($renditions),
            ]);

        } catch (\Exception $e) {
            $error = "Failed to build renditions: {$e->getMessage()}";
            
            $log->markAsFailed($error, ['exception' => $e->getMessage()]);
            
            Log::error("BuildRenditionsJob failed for video {$this->video->id}", [
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
            'width' => $videoStream['width'] ?? 0,
            'height' => $videoStream['height'] ?? 0,
            'codec' => $videoStream['codec_name'] ?? null,
        ];
    }

    protected function determineTargetQualities(int $sourceHeight): array
    {
        $targets = [];

        foreach ($this->qualities as $name => $config) {
            // Don't upscale: only generate if source is larger
            if ($sourceHeight >= $config['height']) {
                $targets[] = $name;
            }
        }

        return $targets;
    }

    protected function createRendition(
        string $inputPath,
        string $qualityName,
        array $config,
        array $probeData
    ): array {
        $outputDir = "videos/{$this->video->uuid}/renditions";
        Storage::disk('public')->makeDirectory($outputDir);

        $outputFilename = "{$qualityName}p.mp4";
        $outputRelative = "{$outputDir}/{$outputFilename}";
        $outputPath = Storage::disk('public')->path($outputRelative);

        $height = $config['height'];
        $maxrate = $config['maxrate'];
        $bufsize = $config['bufsize'];
        $audioBitrate = $config['audio'];

        // Calculate width maintaining aspect ratio (divisible by 2)
        $sourceWidth = $probeData['width'] ?? 1280;
        $sourceHeight = $probeData['height'] ?? 720;
        $aspectRatio = $sourceWidth / max($sourceHeight, 1);
        $width = (int)(round(($height * $aspectRatio) / 2) * 2); // Ensure even number

        $command = implode(' ', [
            'ffmpeg',
            '-y',
            '-i', escapeshellarg($inputPath),
            '-vf', escapeshellarg("scale={$width}:{$height}"),
            '-c:v', 'libx264',
            '-preset', 'veryfast',
            '-crf', '24',
            '-maxrate', "{$maxrate}k",
            '-bufsize', "{$bufsize}k",
            '-g', '48',
            '-keyint_min', '48',
            '-sc_threshold', '0',
            '-c:a', 'aac',
            '-b:a', "{$audioBitrate}k",
            '-movflags', '+faststart',
            '-f', 'mp4',
            escapeshellarg($outputPath),
            '2>&1',
        ]);

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($outputPath)) {
            throw new \Exception("Failed to create {$qualityName}p rendition. Code: {$returnCode}");
        }

        $filesize = filesize($outputPath);

        return [
            'path' => $outputRelative,
            'width' => $width,
            'height' => $height,
            'bitrate_kbps' => $maxrate,
            'filesize' => $filesize,
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("BuildRenditionsJob failed permanently for video {$this->video->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
