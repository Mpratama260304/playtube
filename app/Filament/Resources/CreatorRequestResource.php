<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CreatorRequestResource\Pages;
use App\Models\CreatorRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CreatorRequestResource extends Resource
{
    protected static ?string $model = CreatorRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Creator Requests';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() > 0 ? 'warning' : 'gray';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for Request')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->placeholder('Add notes about your decision...')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.username')
                    ->label('Username')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => '@' . $state),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->reason)
                    ->wrap(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),

                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->placeholder('Not reviewed')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Creator Request')
                    ->modalDescription(fn ($record) => "Are you sure you want to approve {$record->user->name} as a creator? They will be able to upload videos.")
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notes (optional)')
                            ->placeholder('Add any notes about this approval...'),
                    ])
                    ->action(function (CreatorRequest $record, array $data) {
                        $record->approve(auth()->user(), $data['admin_notes'] ?? null);
                        
                        Notification::make()
                            ->title('Request Approved')
                            ->body("{$record->user->name} is now a creator!")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->isPending()),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Creator Request')
                    ->modalDescription(fn ($record) => "Are you sure you want to reject {$record->user->name}'s creator request?")
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Reason for rejection')
                            ->placeholder('Explain why this request is being rejected...')
                            ->required(),
                    ])
                    ->action(function (CreatorRequest $record, array $data) {
                        $record->reject(auth()->user(), $data['admin_notes']);
                        
                        Notification::make()
                            ->title('Request Rejected')
                            ->body("{$record->user->name}'s request has been rejected.")
                            ->warning()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->isPending()),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->isPending()) {
                                    $record->approve(auth()->user(), 'Bulk approved');
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title("{$count} requests approved")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_reject')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('admin_notes')
                                ->label('Reason for rejection')
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->isPending()) {
                                    $record->reject(auth()->user(), $data['admin_notes']);
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title("{$count} requests rejected")
                                ->warning()
                                ->send();
                        }),
                ]),
            ]);
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
            'index' => Pages\ListCreatorRequests::route('/'),
            'view' => Pages\ViewCreatorRequest::route('/{record}'),
        ];
    }
}
