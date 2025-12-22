<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'video_tags');
    }

    public static function findOrCreateFromString(string $name): self
    {
        $slug = Str::slug($name);
        return self::firstOrCreate(
            ['slug' => $slug],
            ['name' => trim($name)]
        );
    }
}
