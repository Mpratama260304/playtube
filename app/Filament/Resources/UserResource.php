<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('username')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => $state ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Role & Status')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->options([
                                'user' => 'User',
                                'admin' => 'Admin',
                            ])
                            ->required()
                            ->default('user'),
                        Forms\Components\Toggle::make('is_creator')
                            ->label('Creator Access')
                            ->helperText('Allow this user to upload videos')
                            ->default(false),
                        Forms\Components\Toggle::make('is_banned')
                            ->label('Banned')
                            ->default(false),
                    ])->columns(3),

                Forms\Components\Section::make('Profile')
                    ->schema([
                        Forms\Components\Textarea::make('bio')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('avatar_path')
                            ->label('Avatar')
                            ->image()
                            ->directory('avatars')
                            ->disk('public'),
                        Forms\Components\FileUpload::make('cover_path')
                            ->label('Cover Image')
                            ->image()
                            ->directory('covers')
                            ->disk('public'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_path')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => $record->avatar_url),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'danger' => 'admin',
                        'success' => 'user',
                    ]),
                Tables\Columns\IconColumn::make('is_creator')
                    ->label('Creator')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\IconColumn::make('is_banned')
                    ->label('Banned')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'user' => 'User',
                        'admin' => 'Admin',
                    ]),
                Tables\Filters\TernaryFilter::make('is_creator')
                    ->label('Creator'),
                Tables\Filters\TernaryFilter::make('is_banned')
                    ->label('Banned'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_creator')
                    ->label(fn (User $record) => $record->is_creator ? 'Revoke Creator' : 'Grant Creator')
                    ->icon(fn (User $record) => $record->is_creator ? 'heroicon-o-x-circle' : 'heroicon-o-check-badge')
                    ->color(fn (User $record) => $record->is_creator ? 'warning' : 'success')
                    ->action(function (User $record) {
                        $record->update([
                            'is_creator' => !$record->is_creator,
                            'creator_approved_at' => !$record->is_creator ? now() : null,
                        ]);
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('ban')
                    ->label('Toggle Ban')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->action(fn (User $record) => $record->update(['is_banned' => !$record->is_banned]))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
