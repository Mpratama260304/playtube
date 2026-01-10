<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoResource\Pages;
use App\Filament\Resources\VideoResource\RelationManagers;
use App\Models\Video;
use App\Services\HlsService;
use App\Services\ThumbnailService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class VideoResource extends Resource
{
    protected static ?string $model = Video::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    
    protected static ?string $navigationGroup = 'Content Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Video Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(5000)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Status & Visibility')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'processing' => 'Processing',
                                'published' => 'Published',
                                'failed' => 'Failed',
                            ])
                            ->required(),
                        Forms\Components\Select::make('visibility')
                            ->options([
                                'public' => 'Public',
                                'unlisted' => 'Unlisted',
                                'private' => 'Private',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('is_short')
                            ->label('Is Short'),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured'),
                    ])->columns(2),

                Forms\Components\Section::make('Processing Info')
                    ->schema([
                        Forms\Components\Placeholder::make('processing_status')
                            ->label('Video Status')
                            ->content(fn ($record) => $record ? match($record->status) {
                                'processing' => '⏳ Video is being processed...',
                                'published' => '✅ Video is ready',
                                'failed' => '❌ Processing failed: ' . ($record->processing_error ?? 'Unknown error'),
                                default => $record->status,
                            } : 'N/A'),
                        Forms\Components\Placeholder::make('original_status')
                            ->label('Original File')
                            ->content(fn ($record) => $record && $record->original_path 
                                ? (Storage::disk('public')->exists($record->original_path) ? '✅ ' . $record->original_path : '⚠️ File missing: ' . $record->original_path)
                                : '❌ No file'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record !== null),

                // HLS Settings Section - Improved with state machine awareness
                Forms\Components\Section::make('HLS Streaming')
                    ->description('Configure adaptive bitrate streaming for this video')
                    ->schema([
                        Forms\Components\Toggle::make('hls_enabled')
                            ->label('Enable HLS Transcoding')
                            ->helperText('When enabled and saved, HLS transcoding will be queued.')
                            ->live()
                            ->default(false),
                        
                        // Improved HLS status display
                        Forms\Components\Placeholder::make('hls_status_display')
                            ->label('HLS Status')
                            ->content(function ($record) {
                                if (!$record) return 'N/A';
                                
                                return self::getHlsStatusHtml($record);
                            }),

                        // Timestamps display
                        Forms\Components\Placeholder::make('hls_timestamps')
                            ->label('Processing Timeline')
                            ->content(function ($record) {
                                if (!$record) return 'N/A';
                                
                                return self::getHlsTimestampsHtml($record);
                            }),

                        // Progress display with intelligent formatting
                        Forms\Components\Placeholder::make('hls_progress_display')
                            ->label('Progress')
                            ->content(function ($record) {
                                if (!$record) return 'N/A';
                                
                                return self::getHlsProgressHtml($record);
                            })
                            ->visible(fn ($record) => $record && in_array($record->processing_state, [
                                Video::PROCESSING_QUEUED, 
                                Video::PROCESSING_RUNNING
                            ])),

                        Forms\Components\Placeholder::make('hls_error')
                            ->label('Error Details')
                            ->content(fn ($record) => $record?->processing_error ?? 'None')
                            ->visible(fn ($record) => $record && $record->processing_state === Video::PROCESSING_FAILED),
                    ])
                    ->columns(1)
                    ->visible(fn ($record) => $record !== null),

                // HLS Health Check Section
                Forms\Components\Section::make('HLS System Health')
                    ->description('System prerequisites for HLS transcoding')
                    ->schema([
                        Forms\Components\Placeholder::make('hls_health')
                            ->label('')
                            ->content(function () {
                                $hlsService = app(HlsService::class);
                                $health = $hlsService->getHealthStatus();
                                
                                return self::getSystemHealthHtml($health);
                            }),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => $record !== null),

                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\FileUpload::make('thumbnail_path')
                            ->label('Thumbnail')
                            ->image()
                            ->directory('videos/thumbnails')
                            ->disk('public'),
                    ]),
            ]);
    }

    /**
     * Generate HLS status HTML with proper state display.
     */
    protected static function getHlsStatusHtml(Video $record): \Illuminate\Support\HtmlString
    {
        // Use single source of truth
        if ($record->isHlsReady()) {
            return new \Illuminate\Support\HtmlString(
                '<span class="text-green-600 font-semibold">✅ HLS Ready</span><br>' .
                '<span class="text-sm text-gray-500">Master playlist exists and accessible</span>'
            );
        }

        $statusLabel = $record->hls_status_label;

        return new \Illuminate\Support\HtmlString(match ($statusLabel) {
            'queued' => '<span class="text-blue-600 font-semibold">⏳ Queued</span><br>' .
                '<span class="text-sm text-gray-500">Waiting for queue worker to pick up job</span>',
            
            'stuck_queued' => '<span class="text-red-600 font-semibold">⚠️ Stuck in Queue</span><br>' .
                '<span class="text-sm text-red-500">Job queued for >2 min. Is queue worker running?</span><br>' .
                '<code class="text-xs bg-gray-100 px-1">php artisan queue:work --queue=hls</code>',
            
            'processing' => '<span class="text-yellow-600 font-semibold">🔄 Processing</span><br>' .
                '<span class="text-sm text-gray-500">FFmpeg is actively transcoding</span>',
            
            'stuck_processing' => '<span class="text-red-600 font-semibold">⚠️ Job May Be Hung</span><br>' .
                '<span class="text-sm text-red-500">No heartbeat for >2 minutes</span>',
            
            'failed' => '<span class="text-red-600 font-semibold">❌ Failed</span><br>' .
                '<span class="text-sm text-red-500">' . e($record->processing_error ?? 'Unknown error') . '</span>',
            
            'pending' => '<span class="text-gray-500 font-semibold">⏸️ Pending</span><br>' .
                '<span class="text-sm text-gray-500">HLS enabled, will process on save</span>',
            
            default => '<span class="text-gray-400">⚪ Disabled</span>',
        });
    }

    /**
     * Generate HLS timestamps HTML.
     */
    protected static function getHlsTimestampsHtml(Video $record): \Illuminate\Support\HtmlString
    {
        $lines = [];

        if ($record->hls_queued_at) {
            $lines[] = "<strong>Queued:</strong> {$record->hls_queued_at->format('M d, H:i:s')}";
        }

        if ($record->hls_started_at) {
            $lines[] = "<strong>Started:</strong> {$record->hls_started_at->format('M d, H:i:s')}";
        }

        if ($record->hls_last_heartbeat_at) {
            $ago = $record->hls_last_heartbeat_at->diffForHumans();
            $isStale = $record->is_heartbeat_stale;
            $status = $isStale ? '⚠️ stale' : '✅ alive';
            $lines[] = "<strong>Last Heartbeat:</strong> {$ago} ({$status})";
        }

        if ($record->processing_finished_at) {
            $lines[] = "<strong>Finished:</strong> {$record->processing_finished_at->format('M d, H:i:s')}";
            
            if ($record->hls_started_at) {
                $duration = $record->hls_started_at->diffInSeconds($record->processing_finished_at);
                $lines[] = "<strong>Duration:</strong> {$duration}s";
            }
        }

        if (empty($lines)) {
            return new \Illuminate\Support\HtmlString('<span class="text-gray-400">Not started</span>');
        }

        return new \Illuminate\Support\HtmlString(implode('<br>', $lines));
    }

    /**
     * Generate HLS progress HTML with smart display.
     */
    protected static function getHlsProgressHtml(Video $record): \Illuminate\Support\HtmlString
    {
        $state = $record->processing_state;
        $progress = $record->processing_progress;

        if ($state === Video::PROCESSING_QUEUED) {
            return new \Illuminate\Support\HtmlString(
                '<div class="flex items-center gap-2">' .
                '<span class="text-blue-600">Waiting for worker...</span>' .
                '</div>'
            );
        }

        if ($state === Video::PROCESSING_RUNNING) {
            // Handle null/0 progress intelligently
            if ($progress === null) {
                return new \Illuminate\Support\HtmlString(
                    '<div class="flex items-center gap-2">' .
                    '<div class="w-full bg-gray-200 rounded-full h-2.5">' .
                    '<div class="bg-yellow-500 h-2.5 rounded-full animate-pulse" style="width: 100%"></div>' .
                    '</div>' .
                    '<span class="text-sm text-gray-500">Estimating...</span>' .
                    '</div>'
                );
            }

            if ($progress === 0) {
                // Check heartbeat to determine if actually processing
                if ($record->hls_last_heartbeat_at && !$record->is_heartbeat_stale) {
                    return new \Illuminate\Support\HtmlString(
                        '<div class="flex items-center gap-2">' .
                        '<div class="w-full bg-gray-200 rounded-full h-2.5">' .
                        '<div class="bg-yellow-500 h-2.5 rounded-full" style="width: 5%"></div>' .
                        '</div>' .
                        '<span class="text-sm text-gray-500">&lt;1% (starting up)</span>' .
                        '</div>'
                    );
                }
            }

            return new \Illuminate\Support\HtmlString(
                '<div class="flex items-center gap-2">' .
                '<div class="w-full bg-gray-200 rounded-full h-2.5">' .
                '<div class="bg-blue-600 h-2.5 rounded-full" style="width: ' . $progress . '%"></div>' .
                '</div>' .
                '<span class="text-sm font-medium">' . $progress . '%</span>' .
                '</div>'
            );
        }

        return new \Illuminate\Support\HtmlString('<span class="text-gray-400">N/A</span>');
    }

    /**
     * Generate system health HTML.
     */
    protected static function getSystemHealthHtml(array $health): \Illuminate\Support\HtmlString
    {
        $lines = [];
        
        // Queue
        $queueIcon = $health['queue_is_sync'] ? '⚠️' : '✅';
        $queueNote = $health['queue_is_sync'] ? ' (sync mode - will block)' : '';
        $lines[] = "**Queue:** {$health['queue_connection']}{$queueNote} {$queueIcon}";
        
        // FFmpeg
        if ($health['ffmpeg_available']) {
            $version = $health['ffmpeg_version'] ? " v{$health['ffmpeg_version']}" : '';
            $lines[] = "**FFmpeg:** ✅ {$health['ffmpeg_path']}{$version}";
        } else {
            $lines[] = "**FFmpeg:** ❌ Not found";
        }
        
        // FFprobe
        if ($health['ffprobe_available']) {
            $version = $health['ffprobe_version'] ? " v{$health['ffprobe_version']}" : '';
            $lines[] = "**FFprobe:** ✅ {$health['ffprobe_path']}{$version}";
        } else {
            $lines[] = "**FFprobe:** ❌ Not found";
        }

        // Queue stats
        $stats = $health['queue_stats'];
        $lines[] = '';
        $lines[] = '**Queue Status:**';
        $lines[] = "- Videos Queued: {$stats['videos_queued']}";
        $lines[] = "- Videos Processing: {$stats['videos_processing']}";
        $lines[] = "- Videos Stuck: {$stats['videos_stuck']}";
        
        if ($health['queue_connection'] === 'database') {
            $lines[] = "- Pending Jobs (DB): {$stats['pending_hls_jobs']}";
        }
        
        // Warnings
        if (!empty($health['warnings'])) {
            $lines[] = '';
            $lines[] = '**Warnings:**';
            foreach ($health['warnings'] as $warning) {
                $lines[] = "- ⚠️ {$warning}";
            }
        }
        
        return new \Illuminate\Support\HtmlString(
            \Illuminate\Support\Str::markdown(implode("\n", $lines))
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ViewColumn::make('thumbnail')
                    ->label('Thumbnail')
                    ->view('filament.columns.thumbnail-image')
                    ->state(fn (Video $record): string => $record->thumbnail_url ?? '/images/placeholder-thumb.svg')
                    ->extraAttributes(['width' => '120px', 'height' => '68px']),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(40)
                    ->wrap(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Channel')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'processing',
                        'success' => 'published',
                        'danger' => 'failed',
                    ]),
                Tables\Columns\BadgeColumn::make('visibility')
                    ->colors([
                        'success' => 'public',
                        'warning' => 'unlisted',
                        'danger' => 'private',
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                // Improved HLS column with proper state display
                Tables\Columns\TextColumn::make('hls_status')
                    ->label('HLS')
                    ->state(fn (Video $record): string => $record->hls_status_label)
                    ->formatStateUsing(function (string $state, Video $record): string {
                        return match ($state) {
                            'ready' => '✅ Ready',
                            'queued' => '⏳ Queued',
                            'stuck_queued' => '⚠️ Stuck (Q)',
                            'processing' => '🔄 ' . ($record->processing_progress ?? '?') . '%',
                            'stuck_processing' => '⚠️ Hung',
                            'failed' => '❌ Failed',
                            'pending' => '⏸️ Pending',
                            default => '⚪ Off',
                        };
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ready' => 'success',
                        'queued', 'pending' => 'info',
                        'processing' => 'warning',
                        'stuck_queued', 'stuck_processing', 'failed' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('views_count')
                    ->label('Views')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'processing' => 'Processing',
                        'published' => 'Published',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('visibility')
                    ->options([
                        'public' => 'Public',
                        'unlisted' => 'Unlisted',
                        'private' => 'Private',
                    ]),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
                Tables\Filters\TernaryFilter::make('is_short')
                    ->label('Shorts'),
                Tables\Filters\TernaryFilter::make('hls_enabled')
                    ->label('HLS Enabled'),
                Tables\Filters\SelectFilter::make('processing_state')
                    ->label('HLS State')
                    ->options([
                        Video::PROCESSING_QUEUED => 'Queued',
                        Video::PROCESSING_RUNNING => 'Processing',
                        Video::PROCESSING_READY => 'Ready',
                        Video::PROCESSING_FAILED => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generateHls')
                    ->label('Generate HLS')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn (Video $record) => $record->has_original && $record->can_generate_hls)
                    ->requiresConfirmation()
                    ->modalHeading('Generate HLS Stream')
                    ->modalDescription('This will queue HLS transcoding. Make sure a queue worker is running.')
                    ->action(function (Video $record) {
                        $record->update(['hls_enabled' => true]);
                        
                        $hlsService = app(HlsService::class);
                        $result = $hlsService->enqueue($record, 'admin_table_action');
                        
                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title('HLS Transcoding Queued')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('HLS Generation Failed')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('retryHls')
                    ->label('Retry HLS')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Video $record) => $record->has_original && $record->processing_state === Video::PROCESSING_FAILED)
                    ->requiresConfirmation()
                    ->modalHeading('Retry HLS Transcoding')
                    ->modalDescription('This will clear the error and queue HLS transcoding again.')
                    ->action(function (Video $record) {
                        // Clear previous state
                        $record->update([
                            'hls_enabled' => true,
                            'processing_state' => null,
                            'processing_progress' => null,
                            'processing_error' => null,
                            'hls_queued_at' => null,
                            'hls_started_at' => null,
                            'hls_last_heartbeat_at' => null,
                        ]);
                        
                        $hlsService = app(HlsService::class);
                        $result = $hlsService->enqueue($record, 'admin_retry');
                        
                        \Filament\Notifications\Notification::make()
                            ->title($result['success'] ? 'HLS Retry Queued' : 'Retry Failed')
                            ->body($result['message'])
                            ->color($result['success'] ? 'success' : 'danger')
                            ->send();
                    }),
                Tables\Actions\Action::make('rebuildHls')
                    ->label('Rebuild HLS')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Video $record) => $record->has_original && $record->isHlsReady())
                    ->requiresConfirmation()
                    ->modalHeading('Rebuild HLS Stream')
                    ->modalDescription('This will delete existing HLS files and regenerate them.')
                    ->action(function (Video $record) {
                        // Delete existing HLS directory
                        $hlsDir = $record->hls_directory;
                        if ($hlsDir) {
                            $fullPath = Storage::disk('public')->path($hlsDir);
                            if (File::isDirectory($fullPath)) {
                                File::deleteDirectory($fullPath);
                            }
                        }
                        
                        $record->update([
                            'hls_master_path' => null,
                            'hls_enabled' => true,
                            'processing_state' => null,
                            'processing_progress' => null,
                            'processing_error' => null,
                            'hls_queued_at' => null,
                            'hls_started_at' => null,
                            'hls_last_heartbeat_at' => null,
                            'processing_started_at' => null,
                            'processing_finished_at' => null,
                        ]);
                        
                        $hlsService = app(HlsService::class);
                        $result = $hlsService->enqueue($record, 'admin_rebuild');
                        
                        \Filament\Notifications\Notification::make()
                            ->title($result['success'] ? 'HLS Rebuild Started' : 'Rebuild Failed')
                            ->body($result['message'])
                            ->color($result['success'] ? 'success' : 'danger')
                            ->send();
                    }),
                Tables\Actions\Action::make('viewLogs')
                    ->label('View Logs')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn (Video $record) => static::getUrl('edit', ['record' => $record]) . '#relation-manager-processinglogs-relation-manager')
                    ->openUrlInNewTab(false),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('generateHlsBulk')
                        ->label('Generate HLS')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Generate HLS for Selected Videos')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $hlsService = app(HlsService::class);
                            $queued = 0;
                            $skipped = 0;
                            $errors = [];
                            
                            foreach ($records as $record) {
                                if ($record->has_original && $record->can_generate_hls) {
                                    $record->update(['hls_enabled' => true]);
                                    $result = $hlsService->enqueue($record, 'admin_bulk_action');
                                    if ($result['success']) {
                                        $queued++;
                                    } else {
                                        $errors[] = "{$record->title}: {$result['message']}";
                                    }
                                } else {
                                    $skipped++;
                                }
                            }
                            
                            $message = "Queued: {$queued}, Skipped: {$skipped}";
                            if (!empty($errors)) {
                                $message .= "\nErrors: " . implode('; ', array_slice($errors, 0, 3));
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('HLS Generation Queued')
                                ->body($message)
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('3s'); // Poll more frequently for better UX
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProcessingLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVideos::route('/'),
            'create' => Pages\CreateVideo::route('/create'),
            'edit' => Pages\EditVideo::route('/{record}/edit'),
        ];
    }
}
