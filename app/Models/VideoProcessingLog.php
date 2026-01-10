<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProcessingLog extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'video_id',
        'job',
        'level',
        'message',
        'context',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
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
        return $query->where('job', $job);
    }

    /**
     * Scope to filter by level.
     */
    public function scopeOfLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Get level badge color for UI.
     */
    public function getLevelColorAttribute(): string
    {
        return match($this->level) {
            'error' => 'danger',
            'warning' => 'warning',
            default => 'info',
        };
    }
}
