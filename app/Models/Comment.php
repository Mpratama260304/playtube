<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    protected $fillable = [
        'video_id',
        'user_id',
        'parent_id',
        'body',
        'likes_count',
    ];

    protected $casts = [
        'likes_count' => 'integer',
    ];

    protected $appends = [
        'created_at_formatted',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class, 'target_id')
            ->where('target_type', 'comment');
    }

    public function scopeRootComments($query)
    {
        return $query->whereNull('parent_id');
    }

    public function getCreatedAtFormattedAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
