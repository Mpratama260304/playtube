<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ThumbnailService
{
    /**
     * Generate thumbnail from video file using FFmpeg.
     * Non-blocking: if FFmpeg fails or is missing, just logs and returns false.
     */
    public function generate(Video $video): bool
    {
        if (!$video->original_path) {
            Log::warning('ThumbnailService: No original_path for video', ['video_id' => $video->id]);
            return false;
        }

        $ffmpeg = $this->findFfmpeg();
        if (!$ffmpeg) {
            Log::info('ThumbnailService: FFmpeg not found, skipping thumbnail generation', ['video_id' => $video->id]);
            return false;
        }

        $sourcePath = storage_path('app/public/' . $video->original_path);
        if (!file_exists($sourcePath)) {
            Log::warning('ThumbnailService: Source video not found', [
                'video_id' => $video->id,
                'path' => $sourcePath
            ]);
            return false;
        }

        $destDir = storage_path('app/public/videos/' . $video->uuid);
        $destPath = $destDir . '/thumb.jpg';

        // Ensure directory exists
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Extract frame at 1 second (with fallback to 0 for very short videos)
        $command = sprintf(
            '%s -y -ss 00:00:01 -i %s -frames:v 1 -q:v 2 -vf "scale=1280:720:force_original_aspect_ratio=decrease,pad=1280:720:(ow-iw)/2:(oh-ih)/2" %s 2>&1',
            escapeshellarg($ffmpeg),
            escapeshellarg($sourcePath),
            escapeshellarg($destPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($destPath) && filesize($destPath) > 0) {
            $video->thumbnail_path = 'videos/' . $video->uuid . '/thumb.jpg';
            $video->save();
            
            Log::info('ThumbnailService: Thumbnail generated successfully', [
                'video_id' => $video->id,
                'path' => $video->thumbnail_path
            ]);
            return true;
        }

        // Fallback: try at 0 seconds for very short videos
        $command = sprintf(
            '%s -y -ss 00:00:00 -i %s -frames:v 1 -q:v 2 -vf "scale=1280:720:force_original_aspect_ratio=decrease,pad=1280:720:(ow-iw)/2:(oh-ih)/2" %s 2>&1',
            escapeshellarg($ffmpeg),
            escapeshellarg($sourcePath),
            escapeshellarg($destPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($destPath) && filesize($destPath) > 0) {
            $video->thumbnail_path = 'videos/' . $video->uuid . '/thumb.jpg';
            $video->save();
            
            Log::info('ThumbnailService: Thumbnail generated (0s fallback)', [
                'video_id' => $video->id,
                'path' => $video->thumbnail_path
            ]);
            return true;
        }

        Log::warning('ThumbnailService: Failed to generate thumbnail', [
            'video_id' => $video->id,
            'return_code' => $returnCode,
            'output' => implode("\n", $output)
        ]);

        return false;
    }

    /**
     * Find FFmpeg executable path.
     * Supports multiple environments: standard Linux, macOS, Replit, Docker, Codespaces
     */
    protected function findFfmpeg(): ?string
    {
        // Check environment variable first
        $envPath = env('FFMPEG_PATH');
        if ($envPath && file_exists($envPath) && is_executable($envPath)) {
            return $envPath;
        }

        $paths = [
            // Standard Linux
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            // macOS Homebrew
            '/opt/homebrew/bin/ffmpeg',
            // Replit / Nix
            getenv('HOME') . '/.nix-profile/bin/ffmpeg',
            '/home/runner/.nix-profile/bin/ffmpeg',
            // Docker/Alpine
            '/usr/local/ffmpeg/bin/ffmpeg',
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Try Nix store paths (Replit)
        $nixMatches = glob('/nix/store/*-ffmpeg-*/bin/ffmpeg');
        if (!empty($nixMatches)) {
            foreach ($nixMatches as $match) {
                if (is_executable($match)) {
                    return $match;
                }
            }
        }

        // Try 'which' command as last resort
        exec('which ffmpeg 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0 && !empty($output[0])) {
            return trim($output[0]);
        }

        // Try 'command -v' (more portable)
        exec('command -v ffmpeg 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0 && !empty($output[0])) {
            return trim($output[0]);
        }

        return null;
    }
}
