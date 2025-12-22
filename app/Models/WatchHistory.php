<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchHistory extends Model
{
    public $timestamps = false;

    protected $table = 'watch_history';

    protected $fillable = [
        'user_id',
        'video_id',
        'last_position_seconds',
        'watched_at',
    ];

    protected $casts = [
        'watched_at' => 'datetime',
        'last_position_seconds' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public static function record(int $userId, int $videoId, int $position = 0): self
    {
        return self::updateOrCreate(
            ['user_id' => $userId, 'video_id' => $videoId],
            ['last_position_seconds' => $position, 'watched_at' => now()]
        );
    }
}
