<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'avatar_path',
        'cover_path',
        'bio',
        'is_banned',
        'is_active',
        'is_creator',
        'creator_approved_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'avatar_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_banned' => 'boolean',
            'is_active' => 'boolean',
            'is_creator' => 'boolean',
            'creator_approved_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() && !$this->is_banned;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subscriptions', 'channel_id', 'subscriber_id')
            ->withPivot('created_at');
    }

    public function subscriptions(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subscriptions', 'subscriber_id', 'channel_id')
            ->withPivot('created_at');
    }

    public function watchLater(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'watch_later')
            ->withPivot('created_at');
    }

    public function watchHistory(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadUserNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->whereNull('read_at');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function conversations()
    {
        return Conversation::where('user_one_id', $this->id)
            ->orWhere('user_two_id', $this->id);
    }

    public function getSubscribersCountAttribute(): int
    {
        return $this->subscribers()->count();
    }

    /**
     * Get avatar URL using relative path for production support.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar_path && Storage::disk('public')->exists($this->avatar_path)) {
            return '/storage/' . $this->avatar_path;
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=random';
    }

    /**
     * Get cover image URL using relative path.
     */
    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_path && Storage::disk('public')->exists($this->cover_path)) {
            return '/storage/' . $this->cover_path;
        }
        return '';
    }

    public function isSubscribedTo(User $channel): bool
    {
        return $this->subscriptions()->where('channel_id', $channel->id)->exists();
    }

    // Creator permission methods
    public function creatorRequests(): HasMany
    {
        return $this->hasMany(CreatorRequest::class);
    }

    public function latestCreatorRequest()
    {
        return $this->hasOne(CreatorRequest::class)->latestOfMany();
    }

    public function isCreator(): bool
    {
        return $this->is_creator || $this->isAdmin();
    }

    public function canUploadVideos(): bool
    {
        return $this->isCreator() && !$this->is_banned;
    }

    public function hasPendingCreatorRequest(): bool
    {
        return $this->creatorRequests()->where('status', 'pending')->exists();
    }

    public function getCreatorStatusAttribute(): string
    {
        if ($this->is_creator) {
            return 'approved';
        }
        
        $latestRequest = $this->latestCreatorRequest;
        if ($latestRequest) {
            return $latestRequest->status;
        }
        
        return 'none';
    }
}
