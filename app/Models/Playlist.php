<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Playlist extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'visibility',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'playlist_videos')
            ->withPivot('position', 'created_at')
            ->orderBy('playlist_videos.position');
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function getVideosCountAttribute(): int
    {
        return $this->videos()->count();
    }

    public function getThumbnailUrlAttribute(): string
    {
        $firstVideo = $this->videos()->first();
        return $firstVideo ? $firstVideo->thumbnail_url : asset('images/default-playlist.jpg');
    }

    public function addVideo(Video $video, ?int $position = null): void
    {
        if ($position === null) {
            $position = $this->videos()->max('playlist_videos.position') + 1;
        }

        $this->videos()->syncWithoutDetaching([
            $video->id => ['position' => $position]
        ]);
    }

    public function removeVideo(Video $video): void
    {
        $this->videos()->detach($video->id);
    }

    public function hasVideo(Video $video): bool
    {
        return $this->videos()->where('videos.id', $video->id)->exists();
    }
}
