<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoResource\Pages;
use App\Models\Video;
use App\Services\ThumbnailService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                            ->live()
                            ->afterStateUpdated(function ($state, $record, $set) {
                                // Prevent publishing if video is not processed
                                if ($state === 'published' && $record) {
                                    if (!$record->hls_master_path && !$record->original_path) {
                                        $set('status', $record->status);
                                        \Filament\Notifications\Notification::make()
                                            ->title('Cannot Publish')
                                            ->body('Video cannot be published because processing has not completed. Please wait for transcoding to finish.')
                                            ->danger()
                                            ->send();
                                    }
                                }
                            }),
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
                            ->label('Processing Status')
                            ->content(fn ($record) => $record ? match($record->status) {
                                'processing' => '⏳ Video is being processed...',
                                'published' => '✅ Video is ready',
                                'failed' => '❌ Processing failed: ' . ($record->processing_error ?? 'Unknown error'),
                                default => $record->status,
                            } : 'N/A'),
                        Forms\Components\Placeholder::make('hls_status')
                            ->label('HLS Ready')
                            ->content(fn ($record) => $record && $record->hls_master_path ? '✅ Yes' : '❌ No'),
                        Forms\Components\Placeholder::make('original_status')
                            ->label('Original File')
                            ->content(fn ($record) => $record && $record->original_path ? '✅ ' . $record->original_path : '❌ No'),
                    ])
                    ->columns(3)
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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Video $record) => $record->status !== 'published')
                    ->requiresConfirmation()
                    ->modalHeading('Publish Video')
                    ->modalDescription(fn (Video $record) => $record->hls_master_path || $record->original_path
                        ? 'Are you sure you want to publish this video?'
                        : 'Warning: This video has not been fully processed. Publishing it may result in playback issues.')
                    ->action(function (Video $record) {
                        if (!$record->hls_master_path && !$record->original_path) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot Publish')
                                ->body('Video cannot be published because no video file is available.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $record->update([
                            'status' => 'published',
                            'published_at' => $record->visibility === 'public' ? now() : $record->published_at,
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Video Published')
                            ->body('The video has been published successfully.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reprocess')
                    ->label('Reprocess')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Video $record) => $record->status === 'failed')
                    ->requiresConfirmation()
                    ->action(function (Video $record) {
                        $record->update([
                            'status' => 'processing',
                            'processing_error' => null,
                        ]);
                        
                        \App\Jobs\ProcessVideoJob::dispatch($record);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Reprocessing Started')
                            ->body('The video has been queued for reprocessing.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('feature')
                    ->label('Toggle Feature')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->action(fn (Video $record) => $record->update(['is_featured' => !$record->is_featured])),
                Tables\Actions\Action::make('regenerateThumbnail')
                    ->label('Regenerate Thumbnail')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->visible(fn (Video $record) => $record->has_original)
                    ->requiresConfirmation()
                    ->modalHeading('Regenerate Thumbnail')
                    ->modalDescription('This will generate a new thumbnail from the original video file.')
                    ->action(function (Video $record) {
                        $thumbnailService = app(ThumbnailService::class);
                        $result = $thumbnailService->generate($record);
                        
                        if ($result) {
                            \Filament\Notifications\Notification::make()
                                ->title('Thumbnail Regenerated')
                                ->body('The thumbnail has been regenerated successfully.')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Regeneration Failed')
                                ->body('Could not regenerate thumbnail. Check if FFmpeg is installed.')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('regenerateThumbnails')
                        ->label('Regenerate Thumbnails')
                        ->icon('heroicon-o-photo')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Regenerate Thumbnails')
                        ->modalDescription('This will regenerate thumbnails for all selected videos that have original files.')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $thumbnailService = app(ThumbnailService::class);
                            $success = 0;
                            $failed = 0;
                            
                            foreach ($records as $record) {
                                if ($record->has_original) {
                                    if ($thumbnailService->generate($record)) {
                                        $success++;
                                    } else {
                                        $failed++;
                                    }
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Thumbnails Regenerated')
                                ->body("Successfully regenerated: {$success}, Failed: {$failed}")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
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
