<?php

namespace App\Filament\Resources\VideoResource\Pages;

use App\Filament\Resources\VideoResource;
use App\Jobs\PrepareStreamMp4Job;
use App\Jobs\BuildRenditionsJob;
use App\Models\Video;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class EditVideo extends EditRecord
{
    protected static string $resource = VideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Reprocess stream MP4 (fast-start)
            Actions\Action::make('reprocessStream')
                ->label('Reprocess Stream')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->visible(fn () => $this->record->has_original && !$this->record->stream_ready)
                ->requiresConfirmation()
                ->modalHeading('Reprocess Stream MP4')
                ->modalDescription('This will create a new fast-start MP4 for instant playback. Make sure a queue worker is running.')
                ->action(function () {
                    // Reset processing state
                    $this->record->update([
                        'processing_state' => Video::PROCESSING_PENDING,
                        'processing_progress' => 0,
                        'processing_error' => null,
                        'stream_ready' => false,
                    ]);
                    
                    // Dispatch the job
                    dispatch(new PrepareStreamMp4Job($this->record))->onQueue('high');
                    
                    Notification::make()
                        ->title('Stream Processing Queued')
                        ->body('The video will be optimized for fast playback. Check the processing logs for progress.')
                        ->success()
                        ->send();
                    
                    $this->record->refresh();
                }),
            
            // Rebuild renditions (quality options)
            Actions\Action::make('rebuildRenditions')
                ->label('Rebuild Renditions')
                ->icon('heroicon-o-squares-plus')
                ->color('warning')
                ->visible(fn () => $this->record->has_original || $this->record->stream_ready)
                ->requiresConfirmation()
                ->modalHeading('Rebuild Quality Renditions')
                ->modalDescription('This will regenerate 360p, 480p, 720p, 1080p versions. Existing renditions will be replaced.')
                ->action(function () {
                    // Delete existing renditions
                    if ($this->record->renditions) {
                        foreach ($this->record->renditions as $quality => $info) {
                            if (isset($info['path']) && Storage::disk('public')->exists($info['path'])) {
                                Storage::disk('public')->delete($info['path']);
                            }
                        }
                    }
                    
                    // Clear renditions
                    $this->record->update(['renditions' => null]);
                    
                    // Dispatch the job
                    dispatch(new BuildRenditionsJob($this->record))->onQueue('default');
                    
                    Notification::make()
                        ->title('Renditions Build Queued')
                        ->body('Multiple quality versions will be created. This may take several minutes.')
                        ->success()
                        ->send();
                    
                    $this->record->refresh();
                }),
            
            // Full reprocess (stream + renditions)
            Actions\Action::make('fullReprocess')
                ->label('Full Reprocess')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->visible(fn () => $this->record->has_original)
                ->requiresConfirmation()
                ->modalHeading('Full Reprocess Video')
                ->modalDescription('This will delete all processed files and regenerate everything from scratch. This cannot be undone.')
                ->action(function () {
                    // Delete stream file
                    if ($this->record->stream_path && Storage::disk('public')->exists($this->record->stream_path)) {
                        Storage::disk('public')->delete($this->record->stream_path);
                    }
                    
                    // Delete renditions directory
                    $renditionsDir = "videos/{$this->record->uuid}/renditions";
                    if (Storage::disk('public')->exists($renditionsDir)) {
                        Storage::disk('public')->deleteDirectory($renditionsDir);
                    }
                    
                    // Reset all processing fields
                    $this->record->update([
                        'stream_path' => null,
                        'stream_ready' => false,
                        'renditions' => null,
                        'processing_state' => Video::PROCESSING_PENDING,
                        'processing_progress' => 0,
                        'processing_error' => null,
                        'processing_started_at' => null,
                        'processing_finished_at' => null,
                    ]);
                    
                    // Dispatch stream job (which will chain to renditions)
                    dispatch(new PrepareStreamMp4Job($this->record))->onQueue('high');
                    
                    Notification::make()
                        ->title('Full Reprocess Queued')
                        ->body('Video will be fully reprocessed. Stream MP4 will be created first, then renditions.')
                        ->success()
                        ->send();
                    
                    $this->record->refresh();
                }),
            
            // Retry failed processing
            Actions\Action::make('retryProcessing')
                ->label('Retry Processing')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record->processing_state === Video::PROCESSING_FAILED)
                ->requiresConfirmation()
                ->modalHeading('Retry Failed Processing')
                ->modalDescription('This will clear the error and attempt to process the video again.')
                ->action(function () {
                    // Clear error state
                    $this->record->update([
                        'processing_state' => Video::PROCESSING_PENDING,
                        'processing_progress' => 0,
                        'processing_error' => null,
                    ]);
                    
                    // Dispatch appropriate job
                    if (!$this->record->stream_ready) {
                        dispatch(new PrepareStreamMp4Job($this->record))->onQueue('high');
                    } else {
                        dispatch(new BuildRenditionsJob($this->record))->onQueue('default');
                    }
                    
                    Notification::make()
                        ->title('Processing Retry Queued')
                        ->body('The video processing has been queued for retry.')
                        ->success()
                        ->send();
                    
                    $this->record->refresh();
                }),
            
            Actions\DeleteAction::make(),
        ];
    }
}