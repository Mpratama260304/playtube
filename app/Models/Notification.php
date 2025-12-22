<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public static function notify(int $userId, string $type, array $data): self
    {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
            'created_at' => now(),
        ]);
    }

    public function getMessageAttribute(): string
    {
        $data = $this->data;

        return match ($this->type) {
            'new_subscriber' => ($data['subscriber_name'] ?? 'Someone') . ' subscribed to your channel',
            'new_comment' => ($data['commenter_name'] ?? 'Someone') . ' commented on your video: ' . ($data['video_title'] ?? ''),
            'new_reply' => ($data['replier_name'] ?? 'Someone') . ' replied to your comment',
            'video_published' => 'Your video "' . ($data['video_title'] ?? '') . '" has been published',
            default => 'You have a new notification',
        };
    }

    public function getLinkAttribute(): ?string
    {
        $data = $this->data;

        return match ($this->type) {
            'new_subscriber' => isset($data['subscriber_username']) ? route('channel.show', $data['subscriber_username']) : null,
            'new_comment', 'new_reply' => isset($data['video_slug']) ? route('video.watch', $data['video_slug']) : null,
            'video_published' => isset($data['video_slug']) ? route('video.watch', $data['video_slug']) : null,
            default => null,
        };
    }
}
