<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    
    protected $primaryKey = 'key';
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'updated_at',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::find($key);
        if (!$setting) {
            return $default;
        }

        $value = $setting->value;

        // Try to decode JSON
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
    }

    public static function getMany(array $keys): array
    {
        $settings = self::whereIn('key', $keys)->pluck('value', 'key');

        $result = [];
        foreach ($keys as $key) {
            if (isset($settings[$key])) {
                $decoded = json_decode($settings[$key], true);
                $result[$key] = json_last_error() === JSON_ERROR_NONE ? $decoded : $settings[$key];
            } else {
                $result[$key] = null;
            }
        }

        return $result;
    }
}
