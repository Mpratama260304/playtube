<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Video extends Model
{
    use HasFactory;
    // Status constants
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';

    // Visibility constants
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_UNLISTED = 'unlisted';
    public const VISIBILITY_PRIVATE = 'private';

    // Processing states (standardized values for HLS state machine)
    public const PROCESSING_PENDING = 'pending';   // Default/initial state
    public const PROCESSING_QUEUED = 'queued';     // Job dispatched, waiting for worker
    public const PROCESSING_RUNNING = 'processing'; // Job actively running (ffmpeg started)
    public const PROCESSING_READY = 'ready';       // Successfully completed
    public const PROCESSING_FAILED = 'failed';     // Failed with error

    protected $fillable = [
        'uuid',
        'slug',
        'user_id',
        'category_id',
        'title',
        'description',
        'visibility',
        'status',
        'is_short',
        'is_featured',
        'duration_seconds',
        'original_path',
        'stream_path',
        'renditions',
        'stream_ready',
        'hls_master_path',
        'thumbnail_path',
        'hls_enabled',
        'processing_error',
        'processing_state',
        'processing_progress',
        'processing_started_at',
        'processing_finished_at',
        'hls_queued_at',
        'hls_started_at',
        'hls_last_heartbeat_at',
        'views_count',
        'likes_count',
        'dislikes_count',
        'comments_count',
        'published_at',
        'processed_at',
        // Embed video fields
        'source_type',
        'embed_url',
        'embed_platform',
        'embed_video_id',
        'embed_iframe_url',
    ];

    protected $casts = [
        'is_short' => 'boolean',
        'is_featured' => 'boolean',
        'stream_ready' => 'boolean',
        'renditions' => 'array',
        'hls_enabled' => 'boolean',
        'published_at' => 'datetime',
        'processed_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processing_finished_at' => 'datetime',
        'hls_queued_at' => 'datetime',
        'hls_started_at' => 'datetime',
        'hls_last_heartbeat_at' => 'datetime',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'dislikes_count' => 'integer',
        'comments_count' => 'integer',
        'duration_seconds' => 'integer',
        'processing_progress' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($video) {
            if (empty($video->uuid)) {
                $video->uuid = (string) Str::uuid();
            }
            if (empty($video->slug)) {
                $video->slug = Str::slug($video->title) . '-' . Str::random(8);
            }
            // Auto-set published_at when creating with published status
            if ($video->status === self::STATUS_PUBLISHED && empty($video->published_at)) {
                $video->published_at = now();
            }
        });

        static::updating(function ($video) {
            // Auto-set published_at when status changes to published
            if ($video->isDirty('status') && 
                $video->status === self::STATUS_PUBLISHED && 
                empty($video->published_at)) {
                $video->published_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'video_tags');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class, 'target_id')
            ->where('target_type', 'video');
    }

    public function views(): HasMany
    {
        return $this->hasMany(View::class);
    }

    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class, 'playlist_videos')
            ->withPivot('position', 'created_at');
    }

    public function processingLogs(): HasMany
    {
        return $this->hasMany(VideoProcessingLog::class)->orderBy('created_at', 'desc');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Normalize a storage path by removing common prefixes.
     * Handles paths like:
     *   - "videos/uuid/thumb.jpg" => "videos/uuid/thumb.jpg"
     *   - "/storage/videos/uuid/thumb.jpg" => "videos/uuid/thumb.jpg"
     *   - "storage/videos/uuid/thumb.jpg" => "videos/uuid/thumb.jpg"
     *   - "public/videos/uuid/thumb.jpg" => "videos/uuid/thumb.jpg"
     *   - "http://..." or "https://..." or "//..." => returned as-is (external URL)
     */
    private function normalizePublicPath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // If it's already a full URL, return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }

        // Strip common prefixes that shouldn't be in storage paths
        $prefixes = ['/storage/', 'storage/', '/public/', 'public/', '/'];
        foreach ($prefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }
        }

        return $path ?: null;
    }

    /**
     * Get the normalized thumbnail path for storage operations.
     */
    public function getNormalizedThumbnailPathAttribute(): ?string
    {
        return $this->normalizePublicPath($this->thumbnail_path);
    }

    /**
     * Get the normalized original video path for storage operations.
     */
    public function getNormalizedOriginalPathAttribute(): ?string
    {
        return $this->normalizePublicPath($this->original_path);
    }

    /**
     * Check if video has a thumbnail path set.
     * No longer checks file existence - Go server handles 404s efficiently.
     */
    public function getHasThumbnailAttribute(): bool
    {
        $path = $this->normalized_thumbnail_path;
        return $path !== null && $path !== '';
    }

    /**
     * Get the thumbnail URL - uses Go server for high performance.
     * Falls back to Laravel /storage path if Go server is disabled.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        $path = $this->normalized_thumbnail_path;
        
        if (!$path) {
            return null;
        }
        
        // Check if it's an external URL
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }

        // Use Go server for thumbnails (fast, <10ms)
        if (config('playtube.use_go_video_server') && $this->uuid) {
            return '/thumb/' . $this->uuid;
        }

        return '/storage/' . $path;
    }

    /**
     * Check if original video file exists on disk.
     */
    public function getHasOriginalAttribute(): bool
    {
        $path = $this->normalized_original_path;
        return $path && Storage::disk('public')->exists($path);
    }

    /**
     * Get the original video URL using relative path (for direct static serving).
     */
    public function getVideoUrlAttribute(): string
    {
        $path = $this->normalized_original_path;
        if ($path && Storage::disk('public')->exists($path)) {
            return '/storage/' . $path;
        }
        return '';
    }

    /**
     * Get the video stream URL with Range request support (for smooth seeking).
     * Uses relative URL to work properly with reverse proxies and tunnels.
     */
    public function getStreamUrlAttribute(): string
    {
        if (!$this->uuid) {
            return '';
        }
        
        // Always use relative URL for same-origin streaming
        return '/stream/' . $this->uuid;
    }

    /**
     * Get the best available playback path.
     * Priority: stream_path (faststart) > original_path
     */
    public function getPlaybackPathAttribute(): ?string
    {
        // Prefer stream_path (faststart optimized)
        if ($this->stream_ready && $this->stream_path) {
            return $this->stream_path;
        }
        
        return $this->original_path;
    }

    /**
     * Check if video has any playable file.
     */
    public function hasPlayableFile(): bool
    {
        // Embedded videos are always playable
        if ($this->isEmbed()) {
            return true;
        }
        
        $path = $this->playback_path;
        return $path && Storage::disk('public')->exists($path);
    }

    /**
     * Check if this is an embedded video (not uploaded).
     */
    public function isEmbed(): bool
    {
        return $this->source_type === 'embed' && !empty($this->embed_iframe_url);
    }

    /**
     * Check if this is an uploaded video.
     */
    public function isUpload(): bool
    {
        return $this->source_type !== 'embed';
    }

    /**
     * Get the embed platform display name.
     */
    public function getEmbedPlatformNameAttribute(): ?string
    {
        if (!$this->embed_platform) {
            return null;
        }
        
        $platforms = [
            'youtube' => 'YouTube',
            'dailymotion' => 'Dailymotion',
            'vimeo' => 'Vimeo',
            'googledrive' => 'Google Drive',
            'facebook' => 'Facebook',
            'twitter' => 'Twitter/X',
            'streamable' => 'Streamable',
            'twitch' => 'Twitch',
            'tiktok' => 'TikTok',
            'rumble' => 'Rumble',
            'bitchute' => 'BitChute',
            'odysee' => 'Odysee',
        ];
        
        return $platforms[$this->embed_platform] ?? ucfirst($this->embed_platform);
    }

    /**
     * Get the HLS master playlist URL using relative path.
     * Uses streaming route for authenticated access.
     * @deprecated HLS has been removed - use stream_url instead
     */
    public function getHlsUrlAttribute(): ?string
    {
        // HLS is deprecated - return null
        return null;
    }

    /**
     * Check if HLS is ready for playback - SINGLE SOURCE OF TRUTH.
     * HLS is only ready if:
     * 1. hls_master_path is set
     * 2. The master playlist file actually exists on disk
     */
    public function isHlsReady(): bool
    {
        if (empty($this->hls_master_path)) {
            return false;
        }
        return Storage::disk('public')->exists($this->hls_master_path);
    }

    /**
     * Accessor for is_hls_ready - delegates to isHlsReady() method.
     */
    public function getIsHlsReadyAttribute(): bool
    {
        return $this->isHlsReady();
    }

    /**
     * Alias for blade compatibility: $video->hls_ready
     */
    public function getHlsReadyAttribute(): bool
    {
        return $this->isHlsReady();
    }

    /**
     * Check if HLS master file exists on disk (same as isHlsReady but more explicit).
     */
    public function getHlsMasterExistsAttribute(): bool
    {
        return $this->isHlsReady();
    }

    /**
     * Check if HLS is currently being processed (queued OR actively processing).
     */
    public function getIsHlsProcessingAttribute(): bool
    {
        return in_array($this->processing_state, [self::PROCESSING_QUEUED, self::PROCESSING_RUNNING]);
    }

    /**
     * Check if HLS job is queued but not yet started.
     */
    public function getIsHlsQueuedAttribute(): bool
    {
        return $this->processing_state === self::PROCESSING_QUEUED;
    }

    /**
     * Check if HLS job is actively running (ffmpeg started).
     */
    public function getIsHlsRunningAttribute(): bool
    {
        return $this->processing_state === self::PROCESSING_RUNNING;
    }

    /**
     * Check if the heartbeat is stale (no update for > 2 minutes).
     */
    public function getIsHeartbeatStaleAttribute(): bool
    {
        if (!$this->hls_last_heartbeat_at) {
            return false;
        }
        return $this->hls_last_heartbeat_at->diffInSeconds(now()) > 120;
    }

    /**
     * Check if video appears stuck in queue (queued for > 2 minutes).
     */
    public function getIsStuckInQueueAttribute(): bool
    {
        if ($this->processing_state !== self::PROCESSING_QUEUED) {
            return false;
        }
        if (!$this->hls_queued_at) {
            return false;
        }
        return $this->hls_queued_at->diffInSeconds(now()) > 120;
    }

    /**
     * Check if video appears stuck in processing (heartbeat stale while processing).
     */
    public function getIsStuckInProcessingAttribute(): bool
    {
        if ($this->processing_state !== self::PROCESSING_RUNNING) {
            return false;
        }
        return $this->is_heartbeat_stale;
    }

    /**
     * Get HLS status label for UI display.
     */
    public function getHlsStatusLabelAttribute(): string
    {
        if ($this->isHlsReady()) {
            return 'ready';
        }

        return match ($this->processing_state) {
            self::PROCESSING_QUEUED => $this->is_stuck_in_queue ? 'stuck_queued' : 'queued',
            self::PROCESSING_RUNNING => $this->is_stuck_in_processing ? 'stuck_processing' : 'processing',
            self::PROCESSING_FAILED => 'failed',
            default => $this->hls_enabled ? 'pending' : 'disabled',
        };
    }

    /**
     * Check if HLS can be generated for this video.
     */
    public function getCanGenerateHlsAttribute(): bool
    {
        return $this->has_original 
            && !$this->is_hls_processing 
            && !$this->isHlsReady();
    }

    /**
     * Get HLS directory path.
     */
    public function getHlsDirectoryAttribute(): string
    {
        return "videos/{$this->uuid}/hls";
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_seconds) {
            return '0:00';
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    // Alias for blade templates
    public function getFormattedDurationAttribute(): string
    {
        return $this->duration_formatted;
    }

    public function getViewsFormattedAttribute(): string
    {
        $count = $this->views_count;
        if ($count >= 1000000000) {
            return round($count / 1000000000, 1) . 'B';
        }
        if ($count >= 1000000) {
            return round($count / 1000000, 1) . 'M';
        }
        if ($count >= 1000) {
            return round($count / 1000, 1) . 'K';
        }
        return (string) $count;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->visibility === self::VISIBILITY_PUBLIC;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isReadyToPublish(): bool
    {
        // Video is ready to publish if HLS or original path exists and not in failed state
        return ($this->hls_master_path || $this->original_path) && 
               $this->status !== self::STATUS_PROCESSING;
    }

    public function canBeViewedBy($user = null): bool
    {
        // Published public videos can be viewed by anyone
        if ($this->status === self::STATUS_PUBLISHED && 
            $this->visibility === self::VISIBILITY_PUBLIC &&
            $this->published_at) {
            return true;
        }

        // No user = guest, can only see published public videos
        if (!$user) {
            return false;
        }

        // Owner can always view their own videos
        if ($user->id === $this->user_id) {
            return true;
        }

        // Admin can view all videos
        if ($user->isAdmin()) {
            return true;
        }

        // Unlisted videos can be viewed if published
        if ($this->status === self::STATUS_PUBLISHED && 
            $this->visibility === self::VISIBILITY_UNLISTED) {
            return true;
        }

        return false;
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNotNull('published_at');
    }

    public function scopeShorts($query)
    {
        return $query->where('is_short', true);
    }

    public function scopeTrending($query, int $days = 7)
    {
        return $query->published()
            ->where('published_at', '>=', now()->subDays($days))
            ->orderByDesc('views_count');
    }

    public function scopeLatest($query)
    {
        return $query->published()->orderByDesc('published_at');
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function syncTags(array $tagNames): void
    {
        $tagIds = collect($tagNames)
            ->filter()
            ->map(fn($name) => Tag::findOrCreateFromString($name)->id)
            ->toArray();

        $this->tags()->sync($tagIds);
    }

    /**
     * Get the optimal stream source for playback
     * Prioritizes: stream_path (faststart MP4) > original_path
     */
    public function getStreamSourceAttribute(): ?string
    {
        if ($this->stream_ready && $this->stream_path) {
            return $this->stream_path;
        }
        
        return $this->original_path;
    }

    /**
     * Get playback URL for the optimal stream source
     */
    public function getPlaybackUrlAttribute(): string
    {
        return '/stream/' . $this->uuid;
    }

    /**
     * Get available quality renditions for quality selector
     * Returns array like: ['360' => ['url' => '...', 'width' => 640, ...], ...]
     */
    public function getAvailableQualitiesAttribute(): array
    {
        if (!$this->renditions || !is_array($this->renditions)) {
            return [];
        }

        $qualities = [];
        foreach ($this->renditions as $quality => $info) {
            if (isset($info['path']) && Storage::disk('public')->exists($info['path'])) {
                $qualities[$quality] = array_merge($info, [
                    'url' => '/stream/' . $this->uuid . '?quality=' . $quality,
                ]);
            }
        }

        return $qualities;
    }

    /**
     * Check if stream MP4 is ready for instant playback
     */
    public function isStreamReady(): bool
    {
        return $this->stream_ready && 
               $this->stream_path && 
               Storage::disk('public')->exists($this->stream_path);
    }

    /**
     * Check if renditions are available
     */
    public function hasRenditions(): bool
    {
        return !empty($this->renditions) && is_array($this->renditions);
    }

    /**
     * Get rendition count
     */
    public function getRenditionCountAttribute(): int
    {
        if (!$this->renditions || !is_array($this->renditions)) {
            return 0;
        }
        
        return count($this->renditions);
    }
}
