<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoResource\Pages;
use App\Models\Video;
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
                Tables\Columns\ImageColumn::make('thumbnail_path')
                    ->label('Thumbnail')
                    ->disk('public')
                    ->width(120)
                    ->height(68),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Channel')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->searchable(),
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
                    ]),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
                Tables\Columns\TextColumn::make('views_count')
                    ->label('Views')
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
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
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
