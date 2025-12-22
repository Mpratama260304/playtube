<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingReports extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Report::query()
                    ->where('status', 'pending')
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('reportable_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                Tables\Columns\TextColumn::make('reason')
                    ->limit(30),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Reporter'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->url(fn (Report $record): string => route('filament.admin.resources.reports.edit', $record))
                    ->icon('heroicon-m-eye')
                    ->color('warning'),
            ]);
    }
}
