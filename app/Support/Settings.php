<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Global helper for retrieving system settings with caching.
 * 
 * S2 - Sustain: Consistent setting retrieval.
 * S3 - Scalable: Cached to reduce database load.
 */
class Settings
{
    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("settings.{$key}", function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Check if a setting exists.
     */
    public static function has(string $key): bool
    {
        return !is_null(self::get($key));
    }
}
