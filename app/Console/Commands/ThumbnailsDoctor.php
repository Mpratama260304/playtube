<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Services\ThumbnailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ThumbnailsDoctor extends Command
{
    protected $signature = 'thumbnails:doctor 
                            {--fix : Attempt to repair and regenerate missing thumbnails}
                            {--limit= : Limit the number of videos to process}
                            {--force : Force regeneration even if thumbnail exists}';

    protected $description = 'Diagnose and repair video thumbnail issues';

    private int $totalChecked = 0;
    private int $withThumbnailPath = 0;
    private int $filesExist = 0;
    private int $filesMissing = 0;
    private int $hasOriginal = 0;
    private int $fixed = 0;
    private int $regenerated = 0;
    private int $failed = 0;

    public function handle(ThumbnailService $thumbnailService): int
    {
        $this->info('');
        $this->info('🔧 PlayTube Thumbnails Doctor');
        $this->info('==============================');
        $this->info('');

        $isFix = $this->option('fix');
        $isForce = $this->option('force');
        $limit = $this->option('limit');

        $query = Video::query()->orderBy('id');
        if ($limit) {
            $query->limit((int) $limit);
        }

        $videos = $query->get();
        $this->totalChecked = $videos->count();

        if ($this->totalChecked === 0) {
            $this->warn('No videos found in database.');
            return 0;
        }

        $this->info("Checking {$this->totalChecked} videos...");
        $this->newLine();

        $issues = [];

        $bar = $this->output->createProgressBar($this->totalChecked);
        $bar->start();

        foreach ($videos as $video) {
            $bar->advance();
            $result = $this->checkVideo($video, $isFix, $isForce, $thumbnailService);
            if ($result) {
                $issues[] = $result;
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary();

        // Display issues table if any
        if (!empty($issues)) {
            $this->newLine();
            $this->warn('Issues Found:');
            $this->table(
                ['Video ID', 'UUID', 'Issue', 'Status'],
                $issues
            );
        }

        // Recommendations
        $this->newLine();
        $this->info('📋 Recommendations:');
        
        if ($this->filesMissing > 0 && !$isFix) {
            $this->line('  • Run with --fix to attempt automatic repair');
        }

        if ($this->failed > 0) {
            $this->line('  • Some thumbnails could not be regenerated - check FFmpeg availability');
            $this->line('  • Run: which ffmpeg');
        }

        // Check storage symlink
        $symlinkPath = public_path('storage');
        $symlinkExists = is_link($symlinkPath) || is_dir($symlinkPath);
        if (!$symlinkExists) {
            $this->error('  ❌ Storage symlink missing! Run: php artisan storage:link');
        } else {
            $this->line('  ✅ Storage symlink exists');
        }

        $this->newLine();

        return $this->failed > 0 ? 1 : 0;
    }

    private function checkVideo(Video $video, bool $fix, bool $force, ThumbnailService $thumbnailService): ?array
    {
        $hasThumbnailPath = !empty($video->thumbnail_path);
        $normalizedPath = $video->normalized_thumbnail_path;
        $fileExists = $normalizedPath && Storage::disk('public')->exists($normalizedPath);
        $hasOriginalPath = !empty($video->original_path);
        $originalExists = $video->has_original;

        if ($hasThumbnailPath) {
            $this->withThumbnailPath++;
        }

        if ($fileExists) {
            $this->filesExist++;
            
            // Force regeneration even if exists
            if ($force && $fix && $originalExists) {
                return $this->regenerateThumbnail($video, $thumbnailService, 'Force regenerated');
            }
            
            return null; // All good
        }

        if ($hasOriginalPath) {
            $this->hasOriginal++;
        }

        // File is missing
        if ($hasThumbnailPath && !$fileExists) {
            $this->filesMissing++;
            
            // Check if path needs normalization fix
            if ($fix && $video->thumbnail_path !== $normalizedPath) {
                // Path format issue - try to fix by normalizing
                $testPath = $normalizedPath;
                if ($testPath && Storage::disk('public')->exists($testPath)) {
                    $video->thumbnail_path = $testPath;
                    $video->save();
                    $this->fixed++;
                    return [$video->id, substr($video->uuid, 0, 8), 'Path normalized', '✅ Fixed'];
                }
            }

            // Try to regenerate from original
            if ($fix && $originalExists) {
                return $this->regenerateThumbnail($video, $thumbnailService, 'Thumbnail missing, regenerated');
            }

            $reason = $originalExists ? 'Thumbnail missing (can regenerate)' : 'Thumbnail missing (no original)';
            return [$video->id, substr($video->uuid, 0, 8), $reason, '❌ Needs fix'];
        }

        // No thumbnail path set at all
        if (!$hasThumbnailPath) {
            $this->filesMissing++;

            if ($fix && $originalExists) {
                return $this->regenerateThumbnail($video, $thumbnailService, 'No thumbnail, generated');
            }

            $reason = $originalExists ? 'No thumbnail (can generate)' : 'No thumbnail, no original';
            return [$video->id, substr($video->uuid, 0, 8), $reason, '⚠️ Warning'];
        }

        return null;
    }

    private function regenerateThumbnail(Video $video, ThumbnailService $thumbnailService, string $successMsg): array
    {
        try {
            $result = $thumbnailService->generate($video);
            
            if ($result) {
                $this->regenerated++;
                return [$video->id, substr($video->uuid, 0, 8), $successMsg, '✅ Fixed'];
            } else {
                $this->failed++;
                Log::warning('ThumbnailsDoctor: Failed to regenerate thumbnail', [
                    'video_id' => $video->id,
                    'uuid' => $video->uuid,
                ]);
                return [$video->id, substr($video->uuid, 0, 8), 'Regeneration failed', '❌ Failed'];
            }
        } catch (\Exception $e) {
            $this->failed++;
            Log::error('ThumbnailsDoctor: Exception during regeneration', [
                'video_id' => $video->id,
                'uuid' => $video->uuid,
                'error' => $e->getMessage(),
            ]);
            return [$video->id, substr($video->uuid, 0, 8), 'Error: ' . substr($e->getMessage(), 0, 30), '❌ Failed'];
        }
    }

    private function displaySummary(): void
    {
        $this->info('📊 Summary');
        $this->line('──────────────────────────────────────');
        $this->line(sprintf('  Total videos checked:       %d', $this->totalChecked));
        $this->line(sprintf('  Videos with thumbnail_path: %d', $this->withThumbnailPath));
        $this->line(sprintf('  Thumbnail files exist:      %d', $this->filesExist));
        $this->line(sprintf('  Thumbnail files missing:    %d', $this->filesMissing));
        $this->line(sprintf('  Videos with original file:  %d', $this->hasOriginal));
        
        if ($this->fixed > 0 || $this->regenerated > 0 || $this->failed > 0) {
            $this->newLine();
            $this->info('🔧 Repair Results');
            $this->line('──────────────────────────────────────');
            $this->line(sprintf('  Paths fixed (normalized):   %d', $this->fixed));
            $this->line(sprintf('  Thumbnails regenerated:     %d', $this->regenerated));
            $this->line(sprintf('  Failed to repair:           %d', $this->failed));
        }
    }
}
