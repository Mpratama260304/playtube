<?php

namespace App\Console\Commands;

use App\Jobs\GenerateHlsSegmentsJob;
use App\Models\Video;
use Illuminate\Console\Command;

class GenerateHlsCommand extends Command
{
    protected $signature = 'playtube:generate-hls 
                            {--video= : UUID of specific video to process}
                            {--all : Process all published videos without HLS}
                            {--force : Regenerate HLS even if already exists}';

    protected $description = 'Generate HLS segments for videos';

    public function handle(): int
    {
        if ($this->option('video')) {
            return $this->processVideo($this->option('video'));
        }

        if ($this->option('all')) {
            return $this->processAllVideos();
        }

        $this->error('Please specify --video=<uuid> or --all');
        return 1;
    }

    protected function processVideo(string $uuid): int
    {
        $video = Video::where('uuid', $uuid)->first();

        if (!$video) {
            $this->error("Video not found: {$uuid}");
            return 1;
        }

        if (!$this->option('force') && $video->hls_status === 'ready') {
            $this->info("Video already has HLS: {$video->title}");
            return 0;
        }

        $this->info("Queuing HLS generation for: {$video->title}");
        GenerateHlsSegmentsJob::dispatch($video);
        $video->update(['hls_status' => 'queued']);

        $this->info('Job queued successfully. Run queue worker to process:');
        $this->line('  php artisan queue:work --queue=hls,default');

        return 0;
    }

    protected function processAllVideos(): int
    {
        $query = Video::published()
            ->whereNotNull('original_path');

        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('hls_status')
                  ->orWhereNotIn('hls_status', ['ready', 'processing', 'queued']);
            });
        }

        $videos = $query->get();

        if ($videos->isEmpty()) {
            $this->info('No videos to process.');
            return 0;
        }

        $this->info("Found {$videos->count()} videos to process.");

        if (!$this->confirm('Continue?')) {
            return 0;
        }

        $bar = $this->output->createProgressBar($videos->count());

        foreach ($videos as $video) {
            GenerateHlsSegmentsJob::dispatch($video);
            $video->update(['hls_status' => 'queued']);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('All jobs queued. Run queue worker to process:');
        $this->line('  php artisan queue:work --queue=hls,default');

        return 0;
    }
}
