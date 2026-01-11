<?php

namespace App\Filament\Resources\CreatorRequestResource\Pages;

use App\Filament\Resources\CreatorRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCreatorRequest extends ViewRecord
{
    protected static string $resource = CreatorRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->isPending())
                ->action(function () {
                    $this->record->approve(auth()->user());
                    $this->redirect(CreatorRequestResource::getUrl('index'));
                }),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->isPending())
                ->action(function () {
                    $this->record->reject(auth()->user(), 'Rejected by admin');
                    $this->redirect(CreatorRequestResource::getUrl('index'));
                }),
        ];
    }
}
