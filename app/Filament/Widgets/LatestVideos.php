<?php

namespace App\Filament\Widgets;

use App\Models\Video;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestVideos extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Video::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\ViewColumn::make('thumbnail')
                    ->label('Thumbnail')
                    ->view('filament.columns.thumbnail-image')
                    ->state(fn (Video $record): string => $record->thumbnail_url ?? '/images/placeholder-thumb.svg')
                    ->extraAttributes(['width' => '80px', 'height' => '45px']),
                Tables\Columns\TextColumn::make('title')
                    ->limit(40)
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Creator')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('views_count')
                    ->label('Views')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'processing',
                        'success' => 'published',
                        'danger' => 'failed',
                        'gray' => 'draft',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Video $record): string => route('filament.admin.resources.videos.edit', $record))
                    ->icon('heroicon-m-eye'),
            ]);
    }
}
