<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Video;
use App\Models\View;
use App\Models\Comment;
use App\Models\Report;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Total Videos', Video::count())
                ->description('Published videos')
                ->descriptionIcon('heroicon-m-video-camera')
                ->color('primary')
                ->chart([3, 5, 7, 4, 6, 8, 5]),

            Stat::make('Total Views', View::count())
                ->description('Video views')
                ->descriptionIcon('heroicon-m-eye')
                ->color('warning')
                ->chart([15, 8, 12, 10, 15, 18, 20]),

            Stat::make('Total Comments', Comment::count())
                ->description('User comments')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('info')
                ->chart([5, 8, 6, 9, 7, 10, 8]),

            Stat::make('Pending Reports', Report::where('status', 'pending')->count())
                ->description('Requires review')
                ->descriptionIcon('heroicon-m-flag')
                ->color('danger'),

            Stat::make('New Users Today', User::whereDate('created_at', today())->count())
                ->description('Registered today')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),
        ];
    }
}
