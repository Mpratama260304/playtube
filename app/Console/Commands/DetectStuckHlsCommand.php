<?php

namespace App\Console\Commands;

use App\Services\HlsService;
use Illuminate\Console\Command;

/**
 * Detect and handle stuck HLS transcoding jobs.
 * 
 * This command finds:
 * 1. Videos stuck in 'queued' state for > 2 minutes (queue worker not running)
 * 2. Videos stuck in 'processing' state with stale heartbeat (job hung)
 * 
 * Usage:
 *   php artisan hls:detect-stuck          # Detect only, don't modify
 *   php artisan hls:detect-stuck --fix    # Detect and mark as failed
 * 
 * Schedule this to run every 5 minutes in production.
 */
class DetectStuckHlsCommand extends Command
{
    protected $signature = 'hls:detect-stuck 
                            {--fix : Actually mark stuck videos as failed (otherwise just report)}
                            {--details : Show detailed information about stuck videos}';

    protected $description = 'Detect and optionally mark stuck HLS transcoding jobs as failed';

    public function handle(HlsService $hlsService): int
    {
        $this->info('🔍 Checking for stuck HLS jobs...');
        $this->newLine();

        $queueStats = $hlsService->getQueueStats();

        // Show current state
        $this->table(
            ['Metric', 'Value'],
            [
                ['Queue Connection', $queueStats['connection']],
                ['Videos Queued', $queueStats['videos_queued']],
                ['Videos Processing', $queueStats['videos_processing']],
                ['Videos Stuck', $queueStats['videos_stuck']],
                ['Pending HLS Jobs (DB)', $queueStats['pending_hls_jobs']],
            ]
        );

        $this->newLine();

        if ($queueStats['videos_stuck'] === 0) {
            $this->info('✅ No stuck videos found.');
            return self::SUCCESS;
        }

        $this->warn("⚠️  Found {$queueStats['videos_stuck']} potentially stuck video(s).");
        $this->newLine();

        if (!$this->option('fix')) {
            $this->line('Run with --fix to mark these as failed:');
            $this->line('  php artisan hls:detect-stuck --fix');
            $this->newLine();
            
            $this->showStuckVideos($hlsService);
            
            return self::SUCCESS;
        }

        // Actually fix stuck videos
        $this->info('Marking stuck videos as failed...');
        $results = $hlsService->detectAndMarkStuckJobs();

        $this->newLine();
        $this->table(
            ['Action', 'Count'],
            [
                ['Stuck in Queue (marked failed)', $results['stuck_in_queue']],
                ['Stuck in Processing (marked failed)', $results['stuck_in_processing']],
                ['Total Fixed', count($results['marked_failed'])],
            ]
        );

        if (!empty($results['marked_failed'])) {
            $this->newLine();
            $this->info('Video IDs marked as failed: ' . implode(', ', $results['marked_failed']));
        }

        $this->newLine();
        $this->info('✅ Done. Failed videos can be retried via admin panel.');

        // Provide actionable advice
        if ($results['stuck_in_queue'] > 0) {
            $this->newLine();
            $this->warn('💡 Videos stuck in queue usually means no queue worker is running.');
            $this->line('   Start a worker with: php artisan queue:work --queue=hls');
        }

        if ($results['stuck_in_processing'] > 0) {
            $this->newLine();
            $this->warn('💡 Videos stuck in processing may indicate:');
            $this->line('   - FFmpeg crashed');
            $this->line('   - Input file is corrupted');
            $this->line('   - Worker was killed/restarted');
        }

        return self::SUCCESS;
    }

    /**
     * Show details of stuck videos.
     */
    protected function showStuckVideos(HlsService $hlsService): void
    {
        $stuckVideos = \App\Models\Video::where(function ($q) {
            $q->where(function ($sub) {
                $sub->where('processing_state', \App\Models\Video::PROCESSING_QUEUED)
                    ->where('hls_queued_at', '<', now()->subMinutes(2));
            })
            ->orWhere(function ($sub) {
                $sub->where('processing_state', \App\Models\Video::PROCESSING_RUNNING)
                    ->where(function ($inner) {
                        $inner->where('hls_last_heartbeat_at', '<', now()->subMinutes(2))
                              ->orWhereNull('hls_last_heartbeat_at');
                    });
            });
        })->get();

        $rows = [];
        foreach ($stuckVideos as $video) {
            $reason = $video->processing_state === \App\Models\Video::PROCESSING_QUEUED
                ? 'Stuck in queue'
                : 'Heartbeat stale';

            $waitTime = $video->processing_state === \App\Models\Video::PROCESSING_QUEUED
                ? ($video->hls_queued_at ? $video->hls_queued_at->diffForHumans() : 'unknown')
                : ($video->hls_last_heartbeat_at ? $video->hls_last_heartbeat_at->diffForHumans() : 'no heartbeat');

            $rows[] = [
                $video->id,
                \Illuminate\Support\Str::limit($video->title, 30),
                $video->processing_state,
                $reason,
                $waitTime,
            ];
        }

        $this->table(
            ['ID', 'Title', 'State', 'Reason', 'Since'],
            $rows
        );
    }
}
