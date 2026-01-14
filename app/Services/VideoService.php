<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class VideoService
{
    public function createVideo(array $data, UploadedFile $file, int $userId): Video
    {
        $uuid = (string) Str::uuid();
        
        // Store the original video
        $originalPath = $this->storeOriginalVideo($file, $uuid);
        
        // Determine visibility
        $visibility = $data['visibility'] ?? 'public';
        
        // Videos are PUBLISHED IMMEDIATELY (playable right away)
        // Background processing (HLS/thumbnail) is just optimization
        $video = Video::create([
            'uuid' => $uuid,
            'user_id' => $userId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'visibility' => $visibility,
            'is_short' => $data['is_short'] ?? false,
            'status' => 'published',  // Always published immediately!
            'original_path' => $originalPath,
            'published_at' => now(),   // Set publish time
            'processing_state' => 'pending', // Track background processing separately
        ]);

        // Sync tags if provided
        if (!empty($data['tags'])) {
            $tags = is_array($data['tags']) 
                ? $data['tags'] 
                : array_map('trim', explode(',', $data['tags']));
            $video->syncTags($tags);
        }

        return $video;
    }

    public function updateVideo(Video $video, array $data): Video
    {
        $video->update([
            'title' => $data['title'] ?? $video->title,
            'description' => $data['description'] ?? $video->description,
            'category_id' => $data['category_id'] ?? $video->category_id,
            'visibility' => $data['visibility'] ?? $video->visibility,
            'is_short' => $data['is_short'] ?? $video->is_short,
        ]);

        if (isset($data['tags'])) {
            $tags = is_array($data['tags']) 
                ? $data['tags'] 
                : array_map('trim', explode(',', $data['tags']));
            $video->syncTags($tags);
        }

        return $video->fresh();
    }

    public function deleteVideo(Video $video): bool
    {
        // Delete video files
        if ($video->original_path) {
            Storage::disk('public')->delete($video->original_path);
        }
        if ($video->thumbnail_path) {
            Storage::disk('public')->delete($video->thumbnail_path);
        }
        if ($video->hls_master_path) {
            $hlsDir = dirname($video->hls_master_path);
            Storage::disk('public')->deleteDirectory($hlsDir);
        }

        // Delete the video directory
        Storage::disk('public')->deleteDirectory('videos/' . $video->uuid);

        return $video->delete();
    }

    protected function storeOriginalVideo(UploadedFile $file, string $uuid): string
    {
        $extension = $file->getClientOriginalExtension() ?: 'mp4';
        $path = "videos/{$uuid}/original.{$extension}";
        
        // Use storeAs with stream to avoid memory exhaustion for large files
        $file->storeAs("videos/{$uuid}", "original.{$extension}", 'public');
        
        return $path;
    }

    public function updateThumbnail(Video $video, UploadedFile $file): Video
    {
        // Delete old thumbnail
        if ($video->thumbnail_path) {
            Storage::disk('public')->delete($video->thumbnail_path);
        }

        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $path = "videos/{$video->uuid}/thumb.{$extension}";
        
        // Use storeAs with stream to avoid memory issues
        $file->storeAs("videos/{$video->uuid}", "thumb.{$extension}", 'public');
        
        $video->update(['thumbnail_path' => $path]);

        return $video;
    }

    /**
     * Auto-generate thumbnail from video using FFmpeg (synchronous, fast)
     * Called during upload if no thumbnail provided
     */
    public function generateThumbnailSync(Video $video): bool
    {
        if (!$video->original_path) {
            return false;
        }

        $ffmpeg = $this->findExecutable('ffmpeg');
        if (!$ffmpeg) {
            Log::warning('FFmpeg not found, cannot auto-generate thumbnail', ['video_id' => $video->id]);
            return false;
        }

        $inputPath = Storage::disk('public')->path($video->original_path);
        $outputPath = Storage::disk('public')->path("videos/{$video->uuid}/thumb.jpg");

        // Ensure directory exists
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Extract frame at 2 seconds (or 0 if video is shorter)
        $command = sprintf(
            '%s -y -i %s -ss 00:00:02 -vframes 1 -vf "scale=1280:720:force_original_aspect_ratio=decrease,pad=1280:720:(ow-iw)/2:(oh-ih)/2" -q:v 2 %s 2>&1',
            escapeshellarg($ffmpeg),
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($outputPath)) {
            $video->update(['thumbnail_path' => "videos/{$video->uuid}/thumb.jpg"]);
            Log::info('Thumbnail auto-generated', ['video_id' => $video->id]);
            return true;
        }

        // Try at 0 seconds if 2 seconds failed (video might be very short)
        $command = sprintf(
            '%s -y -i %s -ss 00:00:00 -vframes 1 -vf "scale=1280:720:force_original_aspect_ratio=decrease,pad=1280:720:(ow-iw)/2:(oh-ih)/2" -q:v 2 %s 2>&1',
            escapeshellarg($ffmpeg),
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($outputPath)) {
            $video->update(['thumbnail_path' => "videos/{$video->uuid}/thumb.jpg"]);
            Log::info('Thumbnail auto-generated (0s fallback)', ['video_id' => $video->id]);
            return true;
        }

        Log::warning('Failed to auto-generate thumbnail', [
            'video_id' => $video->id,
            'return_code' => $returnCode,
            'output' => implode("\n", $output)
        ]);

        return false;
    }

    /**
     * Find FFmpeg/FFprobe executable
     * Supports multiple environments: standard Linux, macOS, Replit, Docker, Codespaces
     */
    protected function findExecutable(string $name): ?string
    {
        // Check environment variable first
        $envKey = strtoupper($name) . '_PATH';
        $envPath = env($envKey);
        if ($envPath && file_exists($envPath) && is_executable($envPath)) {
            return $envPath;
        }

        $paths = [
            '/usr/bin/' . $name,
            '/usr/local/bin/' . $name,
            '/opt/homebrew/bin/' . $name,
            // Replit / Nix
            getenv('HOME') . '/.nix-profile/bin/' . $name,
            '/home/runner/.nix-profile/bin/' . $name,
            // Docker/Alpine
            '/usr/local/ffmpeg/bin/' . $name,
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Try Nix store paths (Replit)
        $nixMatches = glob("/nix/store/*-ffmpeg-*/bin/{$name}");
        if (!empty($nixMatches)) {
            foreach ($nixMatches as $match) {
                if (is_executable($match)) {
                    return $match;
                }
            }
        }

        // Try which command
        exec("which {$name}", $output, $returnCode);
        if ($returnCode === 0 && !empty($output[0])) {
            return trim($output[0]);
        }

        // Try command -v (more portable)
        exec("command -v {$name} 2>/dev/null", $output2, $returnCode2);
        if ($returnCode2 === 0 && !empty($output2[0])) {
            return trim($output2[0]);
        }

        return null;
    }
}
