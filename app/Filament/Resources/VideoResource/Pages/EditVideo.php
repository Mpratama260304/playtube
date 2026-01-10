<?php

namespace App\Filament\Resources\VideoResource\Pages;

use App\Filament\Resources\VideoResource;
use App\Models\Video;
use App\Services\HlsService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVideo extends EditRecord
{
    protected static string $resource = VideoResource::class;

    /**
     * Store original hls_enabled state before save
     */
    protected ?bool $originalHlsEnabled = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateHls')
                ->label('Generate HLS Now')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->visible(fn () => $this->record->has_original && $this->record->can_generate_hls)
                ->requiresConfirmation()
                ->modalHeading('Generate HLS Stream')
                ->modalDescription('This will queue HLS transcoding. Make sure a queue worker is running.')
                ->action(function () {
                    $this->record->update(['hls_enabled' => true]);
                    
                    $hlsService = app(HlsService::class);
                    $result = $hlsService->enqueue($this->record, 'admin_edit_action');
                    
                    Notification::make()
                        ->title($result['success'] ? 'HLS Transcoding Queued' : 'HLS Generation Failed')
                        ->body($result['message'])
                        ->color($result['success'] ? 'success' : 'danger')
                        ->send();
                    
                    $this->record->refresh();
                }),
            Actions\Action::make('retryHls')
                ->label('Retry HLS')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record->has_original && $this->record->processing_state === Video::PROCESSING_FAILED)
                ->requiresConfirmation()
                ->modalHeading('Retry HLS Transcoding')
                ->modalDescription('This will clear the error and queue HLS transcoding again.')
                ->action(function () {
                    // Clear previous state
                    $this->record->update([
                        'hls_enabled' => true,
                        'processing_state' => null,
                        'processing_progress' => null,
                        'processing_error' => null,
                        'hls_queued_at' => null,
                        'hls_started_at' => null,
                        'hls_last_heartbeat_at' => null,
                    ]);
                    
                    $hlsService = app(HlsService::class);
                    $result = $hlsService->enqueue($this->record, 'admin_retry');
                    
                    Notification::make()
                        ->title($result['success'] ? 'HLS Retry Queued' : 'Retry Failed')
                        ->body($result['message'])
                        ->color($result['success'] ? 'success' : 'danger')
                        ->send();
                    
                    $this->record->refresh();
                }),
            Actions\Action::make('rebuildHls')
                ->label('Rebuild HLS')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record->has_original && $this->record->isHlsReady())
                ->requiresConfirmation()
                ->modalHeading('Rebuild HLS Stream')
                ->modalDescription('This will delete existing HLS files and regenerate them.')
                ->action(function () {
                    // Delete existing HLS directory
                    $hlsDir = $this->record->hls_directory;
                    if ($hlsDir) {
                        $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($hlsDir);
                        if (\Illuminate\Support\Facades\File::isDirectory($fullPath)) {
                            \Illuminate\Support\Facades\File::deleteDirectory($fullPath);
                        }
                    }
                    
                    $this->record->update([
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
                    $result = $hlsService->enqueue($this->record, 'admin_rebuild');
                    
                    Notification::make()
                        ->title($result['success'] ? 'HLS Rebuild Queued' : 'Rebuild Failed')
                        ->body($result['message'])
                        ->color($result['success'] ? 'success' : 'danger')
                        ->send();
                    
                    $this->record->refresh();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Hook: Before filling form data - capture original state
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Store original HLS enabled state
        $this->originalHlsEnabled = $data['hls_enabled'] ?? false;
        
        return $data;
    }

    /**
     * Hook: After saving - dispatch HLS job if toggle was switched ON
     */
    protected function afterSave(): void
    {
        /** @var Video $video */
        $video = $this->record;
        
        // Check if HLS was just enabled (toggled from OFF to ON)
        $hlsNowEnabled = $video->hls_enabled;
        $wasDisabled = $this->originalHlsEnabled === false || $this->originalHlsEnabled === null;
        
        if ($hlsNowEnabled && $wasDisabled) {
            // HLS was just enabled - check if we should dispatch
            if ($video->can_generate_hls && $video->has_original) {
                $hlsService = app(HlsService::class);
                $result = $hlsService->enqueue($video, 'admin_toggle_enabled');
                
                if ($result['success']) {
                    Notification::make()
                        ->title('HLS Transcoding Queued')
                        ->body($result['message'])
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('HLS Queue Failed')
                        ->body($result['message'])
                        ->danger()
                        ->send();
                }
            } elseif (!$video->has_original) {
                Notification::make()
                    ->title('HLS Enabled')
                    ->body('HLS is enabled but no original video file found. Upload a video to enable transcoding.')
                    ->warning()
                    ->send();
            } elseif ($video->isHlsReady()) {
                Notification::make()
                    ->title('HLS Already Ready')
                    ->body('HLS is already processed for this video. Use "Rebuild HLS" to regenerate.')
                    ->info()
                    ->send();
            }
        }
        
        // Update original state for next save
        $this->originalHlsEnabled = $video->hls_enabled;
    }
}
