<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoResource\Pages;
use App\Filament\Resources\VideoResource\RelationManagers;
use App\Jobs\PrepareStreamMp4Job;
use App\Jobs\BuildRenditionsJob;
use App\Models\Video;
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
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $record) {
                                // Auto-set published_at when changing to published and it's empty
                                if ($state === 'published' && $record && empty($record->published_at)) {
                                    $set('published_at', now());
                                }
                            }),
                        Forms\Components\Select::make('visibility')
                            ->options([
                                'public' => 'Public',
                                'unlisted' => 'Unlisted',
                                'private' => 'Private',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Published At')
                            ->helperText('Required for video to appear on homepage. Auto-set when status is "Published".')
                            ->nullable(),
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

                // MP4 Streaming Section - Progressive download with quality renditions
                Forms\Components\Section::make('Video Streaming')
                    ->description('Progressive MP4 streaming with multi-quality renditions')
                    ->schema([
                        Forms\Components\Placeholder::make('stream_status_display')
                            ->label('Stream Status')
                            ->content(function ($record) {
                                if (!$record) return 'N/A';
                                
                                return self::getStreamStatusHtml($record);
                            }),

                        Forms\Components\Placeholder::make('renditions_display')
                            ->label('Available Qualities')
                            ->content(function ($record) {
                                if (!$record) return 'N/A';
                                
                                return self::getRenditionsHtml($record);
                            }),

                        // Progress display
                        Forms\Components\Placeholder::make('processing_progress_display')
                            ->label('Progress')
                            ->content(function ($record) {
                                if (!$record) return 'N/A';
                                
                                return self::getProcessingProgressHtml($record);
                            })
                            ->visible(fn ($record) => $record && in_array($record->processing_state, [
                                Video::PROCESSING_QUEUED, 
                                Video::PROCESSING_RUNNING
                            ])),

                        Forms\Components\Placeholder::make('processing_error')
                            ->label('Error Details')
                            ->content(fn ($record) => $record?->processing_error ?? 'None')
                            ->visible(fn ($record) => $record && $record->processing_state === Video::PROCESSING_FAILED),
                    ])
                    ->columns(1)
                    ->visible(fn ($record) => $record !== null),

                // System Health Check Section
                Forms\Components\Section::make('Processing System Health')
                    ->description('System prerequisites for video processing')
                    ->schema([
                        Forms\Components\Placeholder::make('system_health')
                            ->label('')
                            ->content(function () {
                                return self::getSystemHealthHtml(self::checkSystemHealth());
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
     * Generate stream status HTML with proper state display.
     */
    protected static function getStreamStatusHtml(Video $record): \Illuminate\Support\HtmlString
    {
        if ($record->stream_ready && $record->stream_path) {
            return new \Illuminate\Support\HtmlString(
                '<span class="text-green-600 font-semibold">✅ Stream Ready</span><br>' .
                '<span class="text-sm text-gray-500">Fast-start MP4 available for instant playback</span>'
            );
        }

        return new \Illuminate\Support\HtmlString(match ($record->processing_state) {
            Video::PROCESSING_QUEUED => '<span class="text-blue-600 font-semibold">⏳ Queued</span><br>' .
                '<span class="text-sm text-gray-500">Waiting for queue worker to process</span>',
            
            Video::PROCESSING_RUNNING => '<span class="text-yellow-600 font-semibold">🔄 Processing</span><br>' .
                '<span class="text-sm text-gray-500">Creating streamable MP4...</span>',
            
            Video::PROCESSING_FAILED => '<span class="text-red-600 font-semibold">❌ Failed</span><br>' .
                '<span class="text-sm text-red-500">' . e($record->processing_error ?? 'Unknown error') . '</span>',
            
            default => $record->original_path 
                ? '<span class="text-gray-500 font-semibold">⏸️ Not Processed</span><br>' .
                  '<span class="text-sm text-gray-500">Click "Prepare Stream" to create fast-start MP4</span>'
                : '<span class="text-gray-400">⚪ No original file</span>',
        });
    }

    /**
     * Generate renditions HTML showing available qualities.
     */
    protected static function getRenditionsHtml(Video $record): \Illuminate\Support\HtmlString
    {
        $renditions = $record->renditions ?? [];
        
        if (empty($renditions)) {
            return new \Illuminate\Support\HtmlString(
                '<span class="text-gray-400">No renditions yet</span><br>' .
                '<span class="text-sm text-gray-500">Renditions will be generated after stream is ready</span>'
            );
        }

        $badges = [];
        $qualityOrder = ['1080p', '720p', '480p', '360p'];
        
        foreach ($qualityOrder as $quality) {
            if (isset($renditions[$quality])) {
                $badges[] = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">' . $quality . '</span>';
            }
        }

        if (empty($badges)) {
            return new \Illuminate\Support\HtmlString('<span class="text-gray-400">No valid renditions</span>');
        }

        return new \Illuminate\Support\HtmlString(
            '<div class="flex flex-wrap gap-1">' . implode(' ', $badges) . '</div>' .
            '<span class="text-sm text-gray-500 mt-1 block">' . count($badges) . ' quality option(s) available</span>'
        );
    }

    /**
     * Generate processing progress HTML.
     */
    protected static function getProcessingProgressHtml(Video $record): \Illuminate\Support\HtmlString
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
            if ($progress === null || $progress === 0) {
                return new \Illuminate\Support\HtmlString(
                    '<div class="flex items-center gap-2">' .
                    '<div class="w-full bg-gray-200 rounded-full h-2.5">' .
                    '<div class="bg-yellow-500 h-2.5 rounded-full animate-pulse" style="width: 100%"></div>' .
                    '</div>' .
                    '<span class="text-sm text-gray-500">Starting...</span>' .
                    '</div>'
                );
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
     * Check system health for video processing.
     */
    protected static function checkSystemHealth(): array
    {
        $ffmpegPath = config('playtube.ffmpeg_path', '/usr/bin/ffmpeg');
        $ffprobePath = config('playtube.ffprobe_path', '/usr/bin/ffprobe');
        
        $ffmpegAvailable = file_exists($ffmpegPath) && is_executable($ffmpegPath);
        $ffprobeAvailable = file_exists($ffprobePath) && is_executable($ffprobePath);
        
        $queueConnection = config('queue.default');
        
        // Check for stuck jobs (queued for more than 10 minutes without progress)
        $stuckVideos = Video::where('processing_state', Video::PROCESSING_QUEUED)
            ->where('updated_at', '<', now()->subMinutes(10))
            ->count();
        
        // Get pending jobs count from database queue
        $pendingJobs = 0;
        try {
            $pendingJobs = \Illuminate\Support\Facades\DB::table('jobs')->count();
        } catch (\Exception $e) {
            // Ignore if jobs table doesn't exist
        }
        
        return [
            'ffmpeg_available' => $ffmpegAvailable,
            'ffmpeg_path' => $ffmpegPath,
            'ffmpeg_version' => $ffmpegAvailable ? self::getToolVersion($ffmpegPath) : null,
            'ffprobe_available' => $ffprobeAvailable,
            'ffprobe_path' => $ffprobePath,
            'ffprobe_version' => $ffprobeAvailable ? self::getToolVersion($ffprobePath) : null,
            'queue_connection' => $queueConnection,
            'queue_is_sync' => $queueConnection === 'sync',
            'queue_stats' => [
                'videos_queued' => Video::where('processing_state', Video::PROCESSING_QUEUED)->count(),
                'videos_processing' => Video::where('processing_state', Video::PROCESSING_RUNNING)->count(),
                'videos_stuck' => $stuckVideos,
                'pending_jobs' => $pendingJobs,
            ],
            'warnings' => $stuckVideos > 0 ? ['Some videos are stuck in queue. Queue worker may not be running!'] : [],
        ];
    }

    /**
     * Get version of ffmpeg/ffprobe.
     */
    protected static function getToolVersion(string $path): ?string
    {
        $output = shell_exec($path . ' -version 2>/dev/null | head -1');
        if ($output && preg_match('/version\s+([\d.]+)/', $output, $matches)) {
            return $matches[1];
        }
        return null;
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
        $lines[] = '**Processing Status:**';
        $lines[] = "- Videos Queued: {$stats['videos_queued']}";
        $lines[] = "- Videos Processing: {$stats['videos_processing']}";
        $lines[] = "- Pending Jobs in DB: {$stats['pending_jobs']}";
        if ($stats['videos_stuck'] > 0) {
            $lines[] = "- ⚠️ Stuck Videos: {$stats['videos_stuck']} (queued >10min)";
        }
        
        // Warnings
        if (!empty($health['warnings'])) {
            $lines[] = '';
            $lines[] = '**⚠️ Warnings:**';
            foreach ($health['warnings'] as $warning) {
                $lines[] = "- {$warning}";
            }
            $lines[] = '';
            $lines[] = '**💡 Tip:** Use "Process Now" option in actions to process immediately without queue worker.';
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
                // Stream status column
                Tables\Columns\TextColumn::make('stream_status')
                    ->label('Stream')
                    ->state(fn (Video $record): string => $record->stream_ready ? 'ready' : ($record->processing_state ?? 'pending'))
                    ->formatStateUsing(function (string $state, Video $record): string {
                        return match ($state) {
                            'ready' => '✅ Ready',
                            Video::PROCESSING_QUEUED => '⏳ Queued',
                            Video::PROCESSING_RUNNING => '🔄 ' . ($record->processing_progress ?? '?') . '%',
                            Video::PROCESSING_FAILED => '❌ Failed',
                            default => '⚪ Pending',
                        };
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ready' => 'success',
                        Video::PROCESSING_QUEUED => 'info',
                        Video::PROCESSING_RUNNING => 'warning',
                        Video::PROCESSING_FAILED => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                // Renditions column
                Tables\Columns\TextColumn::make('renditions_count')
                    ->label('Qualities')
                    ->state(fn (Video $record): int => count($record->renditions ?? []))
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "{$state} quality" : '—')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
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
                Tables\Filters\TernaryFilter::make('stream_ready')
                    ->label('Stream Ready'),
                Tables\Filters\SelectFilter::make('processing_state')
                    ->label('Processing State')
                    ->options([
                        Video::PROCESSING_QUEUED => 'Queued',
                        Video::PROCESSING_RUNNING => 'Processing',
                        Video::PROCESSING_READY => 'Ready',
                        Video::PROCESSING_FAILED => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('prepareStream')
                    ->label('Prepare Stream')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn (Video $record) => $record->has_original && !$record->stream_ready && !in_array($record->processing_state, [Video::PROCESSING_QUEUED, Video::PROCESSING_RUNNING]))
                    ->requiresConfirmation()
                    ->modalHeading('Prepare Stream MP4')
                    ->modalDescription(function (Video $record) {
                        $fileSize = 0;
                        if ($record->original_path && Storage::disk('public')->exists($record->original_path)) {
                            $fileSize = Storage::disk('public')->size($record->original_path);
                        }
                        
                        $fileSizeMB = round($fileSize / 1024 / 1024, 1);
                        $baseText = 'This will create a fast-start MP4 for instant playback.';
                        
                        if ($fileSizeMB > 200) {
                            return $baseText . "\n\n⚠️ **Large file detected ({$fileSizeMB} MB)**\nSync processing is disabled for files over 200MB to prevent timeout. Please use Queue mode.";
                        }
                        
                        return $baseText . "\n\n📁 File size: {$fileSizeMB} MB";
                    })
                    ->form(function (Video $record) {
                        $fileSize = 0;
                        if ($record->original_path && Storage::disk('public')->exists($record->original_path)) {
                            $fileSize = Storage::disk('public')->size($record->original_path);
                        }
                        
                        $fileSizeMB = $fileSize / 1024 / 1024;
                        $isLargeFile = $fileSizeMB > 200; // 200MB limit for sync (stream prep is faster than renditions)
                        
                        return [
                            \Filament\Forms\Components\Radio::make('process_mode')
                                ->label('Processing Mode')
                                ->options(
                                    $isLargeFile 
                                        ? ['queue' => '⏳ Queue (Background) - Recommended for large files']
                                        : [
                                            'queue' => '⏳ Queue (Background) - Uses queue worker',
                                            'sync' => '⚡ Process Now (Immediate) - Runs directly',
                                        ]
                                )
                                ->default($isLargeFile ? 'queue' : 'sync')
                                ->required()
                                ->helperText($isLargeFile ? '⚠️ Sync mode disabled for files over 200MB to prevent timeout.' : null),
                        ];
                    })
                    ->action(function (Video $record, array $data) {
                        // Check file size
                        $fileSize = 0;
                        if ($record->original_path && Storage::disk('public')->exists($record->original_path)) {
                            $fileSize = Storage::disk('public')->size($record->original_path);
                        }
                        
                        $fileSizeMB = $fileSize / 1024 / 1024;
                        $processMode = $data['process_mode'] ?? 'queue';
                        
                        // Force queue for large files
                        if ($fileSizeMB > 200 && $processMode === 'sync') {
                            $processMode = 'queue';
                            \Filament\Notifications\Notification::make()
                                ->title('Using Queue Mode')
                                ->body('File is too large for sync processing. Switched to queue mode.')
                                ->warning()
                                ->send();
                        }
                        
                        $record->update([
                            'processing_state' => Video::PROCESSING_QUEUED,
                            'processing_error' => null,
                        ]);
                        
                        if ($processMode === 'sync') {
                            // Run synchronously (immediate)
                            try {
                                set_time_limit(300); // 5 minutes max for stream prep
                                $record->update(['processing_state' => Video::PROCESSING_RUNNING]);
                                dispatch_sync(new PrepareStreamMp4Job($record));
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Stream Prepared!')
                                    ->body('Video is now ready for playback.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Processing Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        } else {
                            // Queue for background processing
                            PrepareStreamMp4Job::dispatch($record);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Stream Preparation Queued')
                                ->body('Video will be processed shortly. Make sure queue worker is running.')
                                ->success()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('buildRenditions')
                    ->label('Build Renditions')
                    ->icon('heroicon-o-squares-2x2')
                    ->color('info')
                    ->visible(fn (Video $record) => $record->stream_ready && empty($record->renditions))
                    ->requiresConfirmation()
                    ->modalHeading('Build Quality Renditions')
                    ->modalDescription(function (Video $record) {
                        $fileSize = 0;
                        if ($record->stream_path && Storage::disk('public')->exists($record->stream_path)) {
                            $fileSize = Storage::disk('public')->size($record->stream_path);
                        } elseif ($record->original_path && Storage::disk('public')->exists($record->original_path)) {
                            $fileSize = Storage::disk('public')->size($record->original_path);
                        }
                        
                        $fileSizeMB = round($fileSize / 1024 / 1024, 1);
                        $baseText = 'This will create multiple quality versions (360p, 480p, 720p, 1080p).';
                        
                        if ($fileSizeMB > 100) {
                            return $baseText . "\n\n⚠️ **Large file detected ({$fileSizeMB} MB)**\nSync processing is disabled for files over 100MB to prevent timeout. Please use Queue mode.";
                        }
                        
                        return $baseText . "\n\n📁 File size: {$fileSizeMB} MB";
                    })
                    ->form(function (Video $record) {
                        $fileSize = 0;
                        if ($record->stream_path && Storage::disk('public')->exists($record->stream_path)) {
                            $fileSize = Storage::disk('public')->size($record->stream_path);
                        } elseif ($record->original_path && Storage::disk('public')->exists($record->original_path)) {
                            $fileSize = Storage::disk('public')->size($record->original_path);
                        }
                        
                        $fileSizeMB = $fileSize / 1024 / 1024;
                        $isLargeFile = $fileSizeMB > 100; // 100MB limit for sync
                        
                        return [
                            \Filament\Forms\Components\Radio::make('process_mode')
                                ->label('Processing Mode')
                                ->options(
                                    $isLargeFile 
                                        ? ['queue' => '⏳ Queue (Background) - Recommended for large files']
                                        : [
                                            'queue' => '⏳ Queue (Background)',
                                            'sync' => '⚡ Process Now (Immediate)',
                                        ]
                                )
                                ->default('queue')
                                ->required()
                                ->helperText($isLargeFile ? '⚠️ Sync mode disabled for files over 100MB to prevent timeout.' : null),
                        ];
                    })
                    ->action(function (Video $record, array $data) {
                        // Double-check file size for sync mode
                        $fileSize = 0;
                        if ($record->stream_path && Storage::disk('public')->exists($record->stream_path)) {
                            $fileSize = Storage::disk('public')->size($record->stream_path);
                        } elseif ($record->original_path && Storage::disk('public')->exists($record->original_path)) {
                            $fileSize = Storage::disk('public')->size($record->original_path);
                        }
                        
                        $fileSizeMB = $fileSize / 1024 / 1024;
                        $processMode = $data['process_mode'] ?? 'queue';
                        
                        // Force queue for large files even if somehow sync was selected
                        if ($fileSizeMB > 100 && $processMode === 'sync') {
                            $processMode = 'queue';
                            \Filament\Notifications\Notification::make()
                                ->title('Using Queue Mode')
                                ->body('File is too large for sync processing. Switched to queue mode.')
                                ->warning()
                                ->send();
                        }
                        
                        if ($processMode === 'sync') {
                            try {
                                // Set extended timeout for smaller files
                                set_time_limit(600); // 10 minutes max
                                
                                dispatch_sync(new BuildRenditionsJob($record));
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Renditions Built!')
                                    ->body('Multiple quality versions are now available.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Build Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        } else {
                            BuildRenditionsJob::dispatch($record);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Rendition Build Queued')
                                ->body('Quality versions will be generated. Make sure queue worker is running!')
                                ->success()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('retryProcessing')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Video $record) => $record->has_original && $record->processing_state === Video::PROCESSING_FAILED)
                    ->requiresConfirmation()
                    ->modalHeading('Retry Processing')
                    ->modalDescription(function (Video $record) {
                        $fileSize = 0;
                        if ($record->original_path && Storage::disk('public')->exists($record->original_path)) {
                            $fileSize = Storage::disk('public')->size($record->original_path);
                        }
                        
                        $fileSizeMB = round($fileSize / 1024 / 1024, 1);
                        $baseText = 'This will clear the error and process the video again.';
                        
                        if ($fileSizeMB > 200) {
                            return $baseText . "\n\n⚠️ **Large file ({$fileSizeMB} MB)** - Queue mode recommended.";
                        }
                        
                        return $baseText . "\n\n📁 File size: {$fileSizeMB} MB";
                    })
                    ->form(function (Video $record) {
                        $fileSize = 0;
                        if ($record->original_path && Storage::disk('public')->exists($record->original_path)) {
                            $fileSize = Storage::disk('public')->size($record->original_path);
                        }
                        
                        $fileSizeMB = $fileSize / 1024 / 1024;
                        $isLargeFile = $fileSizeMB > 200;
                        
                        return [
                            \Filament\Forms\Components\Radio::make('process_mode')
                                ->label('Processing Mode')
                                ->options(
                                    $isLargeFile 
                                        ? ['queue' => '⏳ Queue (Background) - Recommended for large files']
                                        : [
                                            'queue' => '⏳ Queue (Background)',
                                            'sync' => '⚡ Process Now (Immediate)',
                                        ]
                                )
                                ->default($isLargeFile ? 'queue' : 'sync')
                                ->required()
                                ->helperText($isLargeFile ? '⚠️ Sync mode disabled for files over 200MB.' : null),
                        ];
                    })
                    ->action(function (Video $record, array $data) {
                        // Check file size
                        $fileSize = 0;
                        if ($record->original_path && Storage::disk('public')->exists($record->original_path)) {
                            $fileSize = Storage::disk('public')->size($record->original_path);
                        }
                        
                        $fileSizeMB = $fileSize / 1024 / 1024;
                        $processMode = $data['process_mode'] ?? 'queue';
                        
                        // Force queue for large files
                        if ($fileSizeMB > 200 && $processMode === 'sync') {
                            $processMode = 'queue';
                        }
                        
                        $record->update([
                            'processing_state' => Video::PROCESSING_QUEUED,
                            'processing_progress' => null,
                            'processing_error' => null,
                            'stream_ready' => false,
                            'stream_path' => null,
                        ]);
                        
                        if ($processMode === 'sync') {
                            try {
                                set_time_limit(300);
                                $record->update(['processing_state' => Video::PROCESSING_RUNNING]);
                                dispatch_sync(new PrepareStreamMp4Job($record));
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Processing Complete!')
                                    ->body('Video is now ready.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Processing Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        } else {
                            PrepareStreamMp4Job::dispatch($record);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Retry Queued')
                                ->body('Processing will restart shortly. Make sure queue worker is running!')
                                ->success()
                                ->send();
                        }
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
                    Tables\Actions\BulkAction::make('prepareStreamBulk')
                        ->label('Prepare Streams')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Prepare Streams for Selected Videos')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $queued = 0;
                            $skipped = 0;
                            
                            foreach ($records as $record) {
                                if ($record->has_original && !$record->stream_ready && !in_array($record->processing_state, [Video::PROCESSING_QUEUED, Video::PROCESSING_RUNNING])) {
                                    $record->update([
                                        'processing_state' => Video::PROCESSING_QUEUED,
                                        'processing_error' => null,
                                    ]);
                                    PrepareStreamMp4Job::dispatch($record);
                                    $queued++;
                                } else {
                                    $skipped++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Stream Preparation Queued')
                                ->body("Queued: {$queued}, Skipped: {$skipped}")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('3s');
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
