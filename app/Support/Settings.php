<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Centralized system setting retrieval with multi-tier resolution and caching.
 *
 * Resolution chain:
 *   1. Runtime overrides (testing/ephemeral)
 *   2. AppInfo (app metadata SSoT)
 *   3. Database (cached)
 *   4. Laravel config (fallback)
 *   5. Default parameter
 *
 * S2 - Sustain: Single API for all setting access patterns.
 * S3 - Scalable: Tiered caching reduces database load.
 */
class Settings
{
    /**
     * Cache key prefix.
     */
    protected const CACHE_PREFIX = 'settings.';

    /**
     * Runtime override values.
     *
     * @var array<string, mixed>
     */
    protected static array $overrides = [];

    /**
     * AppInfo key mapping for SSoT resolution.
     */
    protected static array $appInfoMap = [
        'app_name' => 'name',
        'app_version' => 'version',
        'app_author' => 'author.name',
        'app_support' => 'support',
        'app_license' => 'license',
    ];

    /**
     * Get a setting value with multi-tier resolution.
     *
     * @param string|array<string> $key Single key or array of keys
     */
    public static function get(string|array $key, mixed $default = null, bool $skipCache = false): mixed
    {
        if (is_array($key)) {
            $results = [];
            foreach ($key as $k) {
                $results[$k] = self::resolveSingle($k, $default, $skipCache);
            }

            return $results;
        }

        return self::resolveSingle($key, $default, $skipCache);
    }

    /**
     * Get all settings from the database (cached).
     *
     * @return Collection<string, mixed>
     */
    public static function all(bool $skipCache = false): Collection
    {
        if ($skipCache) {
            Cache::forget(self::CACHE_PREFIX.'all');
        }

        return Cache::rememberForever(
            self::CACHE_PREFIX.'all',
            fn () => Setting::all()->pluck('value', 'key'),
        );
    }

    /**
     * Check if a setting has a non-null value.
     */
    public static function has(string $key): bool
    {
        return ! is_null(self::get($key));
    }

    /**
     * Get all settings belonging to a group (cached).
     */
    public static function group(string $name, bool $skipCache = false): Collection
    {
        if ($skipCache) {
            Cache::forget(self::CACHE_PREFIX.'group.'.$name);
        }

        return Cache::rememberForever(
            self::CACHE_PREFIX.'group.'.$name,
            fn () => Setting::group($name)->get(),
        );
    }

    /**
     * Set runtime overrides (primarily for testing).
     *
     * @param array<string, mixed> $overrides
     */
    public static function override(array $overrides): void
    {
        self::$overrides = array_merge(self::$overrides, $overrides);
    }

    /**
     * Clear all runtime overrides.
     */
    public static function clearOverrides(): void
    {
        self::$overrides = [];
    }

    /**
     * Invalidate cache for a specific key and related group.
     */
    public static function forget(string $key, ?string $group = null): void
    {
        Cache::forget(self::CACHE_PREFIX.$key);

        if ($group) {
            Cache::forget(self::CACHE_PREFIX.'group.'.$group);
        }

        Cache::forget(self::CACHE_PREFIX.'all');
    }

    /**
     * Resolve a single setting key through the tier chain.
     */
    protected static function resolveSingle(string $key, mixed $default, bool $skipCache = false): mixed
    {
        // 1. Runtime overrides
        if (array_key_exists($key, self::$overrides)) {
            return self::$overrides[$key];
        }

        // 2. AppInfo SSoT
        if ($infoValue = self::resolveAppInfoValue($key)) {
            return $infoValue;
        }

        // 3. Database (cached)
        if ($skipCache) {
            Cache::forget(self::CACHE_PREFIX.$key);
        }

        $dbValue = Cache::rememberForever(
            self::CACHE_PREFIX.$key,
            function () use ($key) {
                $setting = Setting::where('key', $key)->first();

                return $setting?->value;
            },
        );

        if (! is_null($dbValue)) {
            return $dbValue;
        }

        // 4. Laravel config fallback
        if (config()->has($key)) {
            return config($key);
        }

        // 5. Default parameter
        return $default;
    }

    /**
     * Map setting keys to AppInfo fields for SSoT resolution.
     */
    protected static function resolveAppInfoValue(string $key): mixed
    {
        if (! isset(self::$appInfoMap[$key])) {
            return null;
        }

        return AppInfo::get(self::$appInfoMap[$key]);
    }
}
