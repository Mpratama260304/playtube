<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DebugThumbnail extends Command
{
    protected $signature = 'debug:thumbnails {--limit=10 : Number of videos to check}';
    protected $description = 'Debug thumbnail status for videos';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        
        $this->info("Checking thumbnails for last {$limit} videos...\n");

        $videos = Video::latest()->take($limit)->get();

        if ($videos->isEmpty()) {
            $this->warn('No videos found.');
            return 0;
        }

        $headers = ['ID', 'UUID', 'Title', 'thumbnail_path', 'File Exists', 'thumbnail_url', 'has_thumbnail'];

        $rows = $videos->map(function ($video) {
            $fileExists = $video->thumbnail_path 
                ? (Storage::disk('public')->exists($video->thumbnail_path) ? '✅ YES' : '❌ NO')
                : '⚪ N/A';

            return [
                $video->id,
                substr($video->uuid, 0, 8) . '...',
                substr($video->title, 0, 25) . (strlen($video->title) > 25 ? '...' : ''),
                $video->thumbnail_path ?? 'NULL',
                $fileExists,
                $video->thumbnail_url ?? 'NULL',
                $video->has_thumbnail ? '✅ YES' : '❌ NO',
            ];
        });

        $this->table($headers, $rows);

        // Summary
        $withThumbnail = $videos->filter(fn($v) => $v->has_thumbnail)->count();
        $withoutThumbnail = $videos->count() - $withThumbnail;

        $this->newLine();
        $this->info("Summary:");
        $this->line("  - Videos with valid thumbnails: {$withThumbnail}");
        $this->line("  - Videos without thumbnails: {$withoutThumbnail}");

        // Check storage symlink
        $symlinkPath = public_path('storage');
        $symlinkExists = is_link($symlinkPath) || is_dir($symlinkPath);
        $this->newLine();
        $this->info("Storage symlink: " . ($symlinkExists ? '✅ EXISTS' : '❌ MISSING (run: php artisan storage:link)'));

        // Show example URL
        if ($videos->first()?->has_thumbnail) {
            $this->newLine();
            $this->info("Example thumbnail URL: " . $videos->first()->thumbnail_url);
        }

        return 0;
    }
}
