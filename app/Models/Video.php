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

    // Processing states (for background optimization, NOT for blocking publish)
    public const PROCESSING_PENDING = 'pending';
    public const PROCESSING_RUNNING = 'processing';
    public const PROCESSING_READY = 'ready';
    public const PROCESSING_FAILED = 'failed';

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
        'hls_master_path',
        'thumbnail_path',
        'processing_error',
        'processing_state',  // Track background processing separately
        'views_count',
        'likes_count',
        'dislikes_count',
        'comments_count',
        'published_at',
        'processed_at',
    ];

    protected $casts = [
        'is_short' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'processed_at' => 'datetime',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'dislikes_count' => 'integer',
        'comments_count' => 'integer',
        'duration_seconds' => 'integer',
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
     * Check if video has a valid thumbnail file on disk.
     */
    public function getHasThumbnailAttribute(): bool
    {
        $path = $this->normalized_thumbnail_path;
        return $path && Storage::disk('public')->exists($path);
    }

    /**
     * Get the thumbnail URL using relative path for proper production support.
     * Returns null if no thumbnail exists (for proper fallback handling in views).
     * Always uses relative paths to work with reverse proxies and HTTPS.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        $path = $this->normalized_thumbnail_path;
        
        // Check if it's an external URL
        if ($path && (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//'))) {
            return $path;
        }

        if ($path && Storage::disk('public')->exists($path)) {
            return '/storage/' . $path;
        }
        
        return null;
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
        if ($this->original_path && Storage::disk('public')->exists($this->original_path)) {
            // Use relative URL path instead of route() to avoid localhost issues
            return '/stream/' . $this->uuid;
        }
        return '';
    }

    /**
     * Get the HLS master playlist URL using relative path.
     */
    public function getHlsUrlAttribute(): ?string
    {
        if ($this->hls_master_path && Storage::disk('public')->exists($this->hls_master_path)) {
            return '/storage/' . $this->hls_master_path;
        }
        return null;
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
}
