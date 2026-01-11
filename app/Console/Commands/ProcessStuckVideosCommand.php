<?php

namespace App\Console\Commands;

use App\Jobs\PrepareStreamMp4Job;
use App\Jobs\ProcessVideoJob;
use App\Models\Video;
use Illuminate\Console\Command;

class ProcessStuckVideosCommand extends Command
{
    protected $signature = 'videos:process-stuck {--force : Force process all videos without stream}';
    protected $description = 'Process any videos stuck in queued/processing state';

    public function handle(): int
    {
        $query = Video::query();
        
        if ($this->option('force')) {
            // Process all videos without stream_ready
            $query->where(function($q) {
                $q->whereNull('stream_ready')
                  ->orWhere('stream_ready', false);
            })->where('status', 'published');
        } else {
            // Only stuck videos
            $query->where(function($q) {
                $q->whereIn('status', ['queued', 'processing'])
                  ->orWhereIn('processing_state', ['queued', 'processing']);
            });
        }
        
        $videos = $query->get();
        
        if ($videos->isEmpty()) {
            $this->info('No stuck videos found.');
            return 0;
        }
        
        $this->info("Found {$videos->count()} videos to process.");
        
        $bar = $this->output->createProgressBar($videos->count());
        $bar->start();
        
        foreach ($videos as $video) {
            $this->newLine();
            $this->info("Processing video {$video->id}: {$video->title}");
            
            try {
                // Update status
                $video->update([
                    'status' => 'processing',
                    'processing_state' => 'processing',
                ]);
                
                // Process metadata
                ProcessVideoJob::dispatchSync($video);
                $video->refresh();
                
                // Process stream MP4
                PrepareStreamMp4Job::dispatchSync($video);
                $video->refresh();
                
                // Mark as ready
                $video->update([
                    'status' => 'published',
                    'processing_state' => 'ready',
                ]);
                
                $this->info("  ✓ Video {$video->id} processed successfully (stream_ready: " . ($video->stream_ready ? 'true' : 'false') . ")");
                
            } catch (\Exception $e) {
                $this->error("  ✗ Video {$video->id} failed: {$e->getMessage()}");
                
                $video->update([
                    'status' => 'published',
                    'processing_state' => 'failed',
                    'processing_error' => $e->getMessage(),
                ]);
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info('Processing complete!');
        
        return 0;
    }
}
