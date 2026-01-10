<?php

namespace App\Filament\Resources\VideoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProcessingLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'processingLogs';

    protected static ?string $title = 'HLS Processing Logs';

    protected static ?string $recordTitleAttribute = 'message';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('M d, H:i:s')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at?->diffForHumans()),
                Tables\Columns\BadgeColumn::make('level')
                    ->colors([
                        'info' => 'info',
                        'warning' => 'warning',
                        'error' => 'danger',
                    ]),
                Tables\Columns\TextColumn::make('message')
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn ($record) => $record->message),
                Tables\Columns\TextColumn::make('context')
                    ->label('Details')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) return '-';
                        if (is_array($state)) {
                            // Show key info from context
                            $parts = [];
                            if (isset($state['progress'])) {
                                $parts[] = "Progress: {$state['progress']}%";
                            }
                            if (isset($state['reason'])) {
                                $parts[] = "Reason: {$state['reason']}";
                            }
                            if (isset($state['queue_connection'])) {
                                $parts[] = "Queue: {$state['queue_connection']}";
                            }
                            if (isset($state['elapsed_seconds'])) {
                                $parts[] = "Elapsed: {$state['elapsed_seconds']}s";
                            }
                            if (isset($state['current_out_time'])) {
                                $parts[] = "Out: {$state['current_out_time']}s";
                            }
                            if (isset($state['rendition'])) {
                                $parts[] = "Rendition: {$state['rendition']}";
                            }
                            return implode(' | ', $parts) ?: json_encode($state);
                        }
                        return $state;
                    })
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'info' => 'Info',
                        'warning' => 'Warning',
                        'error' => 'Error',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Log Entry Details')
                    ->form([
                        Forms\Components\TextInput::make('created_at')
                            ->label('Time')
                            ->disabled(),
                        Forms\Components\TextInput::make('level')
                            ->disabled(),
                        Forms\Components\Textarea::make('message')
                            ->disabled()
                            ->rows(2),
                        Forms\Components\Textarea::make('context')
                            ->label('Full Context (JSON)')
                            ->disabled()
                            ->rows(10)
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('2s')  // Poll every 2 seconds for realtime updates during processing
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
