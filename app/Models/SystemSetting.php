<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $setting) {
            Cache::forget("system_setting_{$setting->key}");
            Cache::forget('system_settings_all');
            Cache::forget('system_message_pricing');
        });

        static::deleted(function (self $setting) {
            Cache::forget("system_setting_{$setting->key}");
            Cache::forget('system_settings_all');
            Cache::forget('system_message_pricing');
        });
    }

    /**
     * Get a setting value by key with caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("system_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            $val = $setting->value;

            // Try decoding JSON if possible
            $decoded = json_decode($val, true);
            if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
                return $decoded;
            }

            return $val;
        });
    }

    /**
     * Set a setting value by key and invalidate cache immediately.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): self
    {
        $storedValue = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;

        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $storedValue, 'group' => $group]
        );

        Cache::forget("system_setting_{$key}");
        Cache::forget('system_settings_all');
        Cache::forget('system_message_pricing');

        return $setting;
    }
}
