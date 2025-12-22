<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reaction extends Model
{
    protected $fillable = [
        'user_id',
        'target_type',
        'target_id',
        'reaction',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function target()
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    public static function toggle(int $userId, string $targetType, int $targetId, string $reaction): array
    {
        $existing = self::where([
            'user_id' => $userId,
            'target_type' => $targetType,
            'target_id' => $targetId,
        ])->first();

        if ($existing) {
            if ($existing->reaction === $reaction) {
                // Remove reaction
                $existing->delete();
                return ['action' => 'removed', 'reaction' => null];
            } else {
                // Change reaction
                $existing->update(['reaction' => $reaction]);
                return ['action' => 'changed', 'reaction' => $reaction];
            }
        }

        // Create new reaction
        self::create([
            'user_id' => $userId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'reaction' => $reaction,
        ]);

        return ['action' => 'added', 'reaction' => $reaction];
    }
}
