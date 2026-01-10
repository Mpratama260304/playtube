<?php

namespace App\Filament\Widgets;

use App\Models\Video;
use App\Services\HlsService;
use Filament\Notifications\Notification;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HlsQueueHealthWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '10s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $hlsService = app(HlsService::class);
        $health = $hlsService->getHealthStatus();
        $stats = $health['queue_stats'];

        return [
            Stat::make('Queue Driver', $health['queue_connection'])
                ->description($health['queue_is_sync'] ? '⚠️ Sync mode (blocking)' : '✅ Async mode')
                ->color($health['queue_is_sync'] ? 'warning' : 'success')
                ->icon('heroicon-o-queue-list'),

            Stat::make('FFmpeg', $health['ffmpeg_available'] ? '✅ Available' : '❌ Missing')
                ->description($health['ffmpeg_version'] ? "v{$health['ffmpeg_version']}" : ($health['ffmpeg_available'] ? $health['ffmpeg_path'] : 'Install required'))
                ->color($health['ffmpeg_available'] ? 'success' : 'danger')
                ->icon('heroicon-o-film'),

            Stat::make('Videos Queued', (string) $stats['videos_queued'])
                ->description('Waiting for worker')
                ->color($stats['videos_queued'] > 0 ? 'info' : 'gray')
                ->icon('heroicon-o-clock'),

            Stat::make('Videos Processing', (string) $stats['videos_processing'])
                ->description('FFmpeg running')
                ->color($stats['videos_processing'] > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-cog-6-tooth'),

            Stat::make('Stuck Videos', (string) $stats['videos_stuck'])
                ->description($stats['videos_stuck'] > 0 ? '⚠️ Needs attention' : 'All healthy')
                ->color($stats['videos_stuck'] > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),

            Stat::make('Pending Jobs (DB)', (string) $stats['pending_hls_jobs'])
                ->description('In jobs table')
                ->color($stats['pending_hls_jobs'] > 0 ? 'info' : 'gray')
                ->icon('heroicon-o-inbox-stack'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Widgets\StatsOverviewWidget\Actions\Action::make('detectStuck')
                ->label('Detect & Fix Stuck')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Fix Stuck HLS Jobs')
                ->modalDescription('This will mark any videos stuck in queue/processing as failed. They can then be retried.')
                ->action(function () {
                    $hlsService = app(HlsService::class);
                    $results = $hlsService->detectAndMarkStuckJobs();

                    $total = $results['stuck_in_queue'] + $results['stuck_in_processing'];

                    if ($total === 0) {
                        Notification::make()
                            ->title('No Stuck Videos')
                            ->body('All videos are processing normally.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Fixed Stuck Videos')
                            ->body("Marked {$total} video(s) as failed. You can retry them from the Videos list.")
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }
}
