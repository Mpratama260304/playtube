<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProcessingLog extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'video_id',
        'job_type',
        'status',
        'message',
        'metadata',
        'progress',
        'started_at',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'progress' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Scope to filter by job type.
     */
    public function scopeForJob($query, string $job)
    {
        return $query->where('job_type', $job);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Update progress with optional message.
     */
    public function updateProgress(int $progress, ?string $message = null): self
    {
        $data = [
            'progress' => $progress,
            'updated_at' => now(),
        ];

        if ($message) {
            $data['message'] = $message;
        }

        $this->update($data);
        return $this;
    }

    /**
     * Mark this log entry as completed.
     */
    public function markAsCompleted(string $message, array $metadata = []): self
    {
        $this->update([
            'status' => 'completed',
            'progress' => 100,
            'message' => $message,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
            'completed_at' => now(),
            'updated_at' => now(),
        ]);
        return $this;
    }

    /**
     * Mark this log entry as failed.
     */
    public function markAsFailed(string $message, array $metadata = []): self
    {
        $this->update([
            'status' => 'error',
            'message' => $message,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
            'completed_at' => now(),
            'updated_at' => now(),
        ]);
        return $this;
    }

    /**
     * Get status badge color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'error' => 'danger',
            'warning' => 'warning',
            'completed' => 'success',
            'processing' => 'primary',
            default => 'info',
        };
    }
}
