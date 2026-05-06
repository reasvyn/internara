<?php

declare(strict_types=1);

namespace App\Support;

use App\Domain\Core\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
final class Settings
{
    protected const CACHE_PREFIX = 'settings.';

    /**
     * @var array<string, mixed>
     */
    protected static array $overrides = [];

    protected static array $appInfoMap = [
        'app_name' => 'name',
        'app_version' => 'version',
        'app_author' => 'author.name',
        'app_support' => 'support',
        'app_license' => 'license',
    ];

    /**
     * Log a database query exception for settings operations.
     *
     * @param array<string, mixed> $context
     */
    protected static function logQueryError(string $message, QueryException $e, array $context = []): void
    {
        $context['error'] = $e->getMessage();

        Log::error($message, $context);
    }

    /**
     * Log a database query warning for settings operations.
     *
     * @param array<string, mixed> $context
     */
    protected static function logQueryWarning(string $message, QueryException $e, array $context = []): void
    {
        $context['error'] = $e->getMessage();

        Log::warning($message, $context);
    }

    /**
     * Get a setting value with multi-tier resolution.
     *
     * @param string|array<string> $key Single key or array of keys
     */
    public static function get(
        string|array $key,
        mixed $default = null,
        bool $skipCache = false,
    ): mixed {
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

        try {
            return Cache::rememberForever(
                self::CACHE_PREFIX.'all',
                fn () => Setting::all()->pluck('value', 'key'),
            );
        } catch (QueryException $e) {
            self::logQueryError('Failed to fetch all settings from database', $e);

            return collect();
        }
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

        try {
            return Cache::rememberForever(
                self::CACHE_PREFIX.'group.'.$name,
                fn () => Setting::group($name)->get(),
            );
        } catch (QueryException $e) {
            self::logQueryError('Failed to fetch settings group from database', $e, [
                'group' => $name,
            ]);

            return collect();
        }
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
     * Update or create multiple settings in bulk.
     *
     * Each entry can be a simple value or an array with 'value' and additional attributes
     * (type, description, group). Keys must be lowercase snake_case.
     *
     * @param array<string, mixed> $settings
     */
    public static function set(array $settings): int
    {
        $updated = 0;

        foreach ($settings as $key => $attributes) {
            $value = is_array($attributes) ? ($attributes['value'] ?? null) : $attributes;
            $extra = is_array($attributes) ? array_diff_key($attributes, ['value' => null]) : [];

            $model = Setting::updateOrCreate(
                ['key' => $key],
                array_merge(['value' => $value], $extra),
            );

            if ($model->wasRecentlyCreated || $model->wasChanged()) {
                self::forget($key, $model->group);

                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Check if a group has any settings.
     */
    public static function hasGroup(string $name): bool
    {
        try {
            return Setting::group($name)->exists();
        } catch (QueryException $e) {
            self::logQueryWarning('Failed to check settings group existence', $e, [
                'group' => $name,
            ]);

            return false;
        }
    }

    /**
     * Invalidate cache for an entire settings group.
     */
    public static function forgetGroup(string $name): void
    {
        Cache::forget(self::CACHE_PREFIX.'group.'.$name);
        Cache::forget(self::CACHE_PREFIX.'all');

        try {
            $keys = Setting::group($name)->pluck('key');

            foreach ($keys as $key) {
                Cache::forget(self::CACHE_PREFIX.$key);
            }
        } catch (QueryException $e) {
            self::logQueryWarning('Failed to forget group cache keys', $e, [
                'group' => $name,
            ]);
        }
    }

    /**
     * Get all distinct setting keys from the database.
     *
     * @return Collection<int, string>
     */
    public static function keys(bool $skipCache = false): Collection
    {
        if ($skipCache) {
            Cache::forget(self::CACHE_PREFIX.'keys');
        }

        try {
            return Cache::rememberForever(
                self::CACHE_PREFIX.'keys',
                fn () => Setting::query()->orderBy('key')->pluck('key'),
            );
        } catch (QueryException $e) {
            self::logQueryError('Failed to fetch setting keys', $e);

            return collect();
        }
    }

    /**
     * Count settings by group.
     *
     * @return Collection<string, int>
     */
    public static function countByGroup(): Collection
    {
        try {
            return Setting::query()
                ->selectRaw('`group`, COUNT(*) as count')
                ->whereNotNull('group')
                ->groupBy('group')
                ->pluck('count', 'group');
        } catch (QueryException $e) {
            self::logQueryError('Failed to count settings by group', $e);

            return collect();
        }
    }

    /**
     * Get all distinct group names.
     *
     * @return Collection<int, string>
     */
    public static function groups(): Collection
    {
        return Setting::groups();
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

        // Also invalidate any group cache this key might belong to
        // by fetching the setting's group from the database
        try {
            $setting = Setting::where('key', $key)->first();

            if ($setting?->group) {
                Cache::forget(self::CACHE_PREFIX.'group.'.$setting->group);
            }
        } catch (QueryException $e) {
            self::logQueryWarning('Failed to invalidate setting group cache', $e, [
                'key' => $key,
            ]);
        }
    }

    /**
     * Resolve a single setting key through the tier chain.
     */
    protected static function resolveSingle(
        string $key,
        mixed $default,
        bool $skipCache = false,
    ): mixed {
        // 1. Runtime overrides
        if (array_key_exists($key, self::$overrides)) {
            return self::$overrides[$key];
        }

        // 2. AppInfo SSoT (use !== null to preserve falsy values like 0, false, '')
        $infoValue = self::resolveAppInfoValue($key);

        if ($infoValue !== null) {
            return $infoValue;
        }

        // 3. Database (cached)
        if ($skipCache) {
            Cache::forget(self::CACHE_PREFIX.$key);
        }

        try {
            $dbValue = Cache::rememberForever(self::CACHE_PREFIX.$key, function () use ($key) {
                $setting = Setting::where('key', $key)->first();

                return $setting?->value;
            });
        } catch (QueryException $e) {
            self::logQueryWarning('Failed to resolve setting from database', $e, [
                'key' => $key,
            ]);

            $dbValue = null;
        }

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
