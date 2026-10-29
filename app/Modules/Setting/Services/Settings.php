<?php

declare(strict_types=1);

namespace App\Modules\Setting\Services;

use App\Modules\Core\Services\AppInfo;
use App\Modules\Core\Services\SmartLogger;
use App\Modules\Setting\Models\Setting;
use App\Modules\Setting\Support\Brand;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class Settings
{
    private const NOT_FOUND_SENTINEL = '__SETTING_NOT_FOUND__';

    protected static array $overrides = [];

    protected static array $runtime = [];

    protected static array $appInfoKeys = ['name', 'version', 'support', 'license'];

    protected static array $appInfoAliases = [
        'app_name' => 'name',
        'app_version' => 'version',
        'app_support' => 'support',
        'app_license' => 'license',
        'author' => 'author.name',
    ];

    private static function logQuery(
        string $level,
        string $message,
        QueryException $e,
        array $context = [],
    ): void {
        SmartLogger::{$level}($message)
            ->withPayload(array_merge($context, ['error' => $e->getMessage()]))
            ->withPiiMasking()
            ->systemOnly()
            ->save();
    }

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

    public static function all(bool $skipCache = false): Collection
    {
        if ($skipCache) {
            Cache::forget(config('cache-keys.settings_all'));
        }

        try {
            return Cache::rememberForever(
                config('cache-keys.settings_all'),
                // Hydrate the models instead of plucking raw values: the value
                // cast must apply, otherwise encrypted settings come back as
                // ciphertext and JSON settings as strings.
                fn () => Setting::query()
                    ->get(['key', 'type', 'value'])
                    ->mapWithKeys(fn (Setting $setting): array => [
                        $setting->key => $setting->value,
                    ]),
            );
        } catch (QueryException $e) {
            self::logQuery('error', 'Failed to fetch all setting from database', $e);

            return collect();
        }
    }

    public static function has(string $key): bool
    {
        return ! is_null(self::get($key));
    }

    public static function group(string $name, bool $skipCache = false): Collection
    {
        if ($skipCache) {
            Cache::forget(config('cache-keys.settings_group').$name);
        }

        try {
            return Cache::rememberForever(
                config('cache-keys.settings_group').$name,
                fn () => Setting::group($name)->get(),
            );
        } catch (QueryException $e) {
            self::logQuery('error', 'Failed to fetch setting group from database', $e, [
                'group' => $name,
            ]);

            return collect();
        }
    }

    public static function clearRuntime(): void
    {
        self::$runtime = [];
    }

    public static function override(array $overrides): void
    {
        self::$overrides = array_merge(self::$overrides, $overrides);
        self::$runtime = [];
    }

    public static function clearOverrides(): void
    {
        self::$overrides = [];
        self::$runtime = [];
    }

    public static function hasGroup(string $name): bool
    {
        try {
            return Setting::group($name)->exists();
        } catch (QueryException $e) {
            self::logQuery('warning', 'Failed to check setting group existence', $e, [
                'group' => $name,
            ]);

            return false;
        }
    }

    public static function forgetGroup(string $name): void
    {
        self::$runtime = [];
        Cache::forget(config('cache-keys.settings_group').$name);
        Cache::forget(config('cache-keys.settings_all'));
        Cache::forget(config('cache-keys.theme_css_variables'));

        try {
            $keys = Setting::group($name)->pluck('key');

            foreach ($keys as $key) {
                Cache::forget(config('cache-keys.settings_key').$key);
            }
        } catch (QueryException $e) {
            self::logQuery('warning', 'Failed to forget group cache keys', $e, [
                'group' => $name,
            ]);
        }
    }

    public static function keys(bool $skipCache = false): Collection
    {
        if ($skipCache) {
            Cache::forget(config('cache-keys.settings_keys'));
        }

        try {
            return Cache::rememberForever(
                config('cache-keys.settings_keys'),
                fn () => Setting::query()->orderBy('key')->pluck('key'),
            );
        } catch (QueryException $e) {
            self::logQuery('error', 'Failed to fetch setting keys', $e);

            return collect();
        }
    }

    public static function countByGroup(): Collection
    {
        try {
            return Setting::query()
                ->selectRaw('`group`, COUNT(*) as count')
                ->whereNotNull('group')
                ->groupBy('group')
                ->pluck('count', 'group');
        } catch (QueryException $e) {
            self::logQuery('error', 'Failed to count setting by group', $e);

            return collect();
        }
    }

    public static function groups(): Collection
    {
        try {
            return Setting::query()
                ->select('group')
                ->distinct()
                ->whereNotNull('group')
                ->orderBy('group')
                ->pluck('group');
        } catch (QueryException $e) {
            self::logQuery('warning', 'Failed to fetch setting groups', $e);

            return collect();
        }
    }

    public static function forget(string $key, ?string $group = null): void
    {
        unset(self::$runtime[$key]);
        Cache::forget(config('cache-keys.settings_key').$key);

        if ($group !== null) {
            Cache::forget(config('cache-keys.settings_group').$group);
        } else {
            try {
                $setting = Setting::where('key', $key)->first();

                if ($setting?->group) {
                    Cache::forget(config('cache-keys.settings_group').$setting->group);
                }
            } catch (QueryException $e) {
                self::logQuery('warning', 'Failed to invalidate setting group cache', $e, [
                    'key' => $key,
                ]);
            }
        }

        Cache::forget(config('cache-keys.settings_all'));

        if (in_array($key, config('settings.theme_cache_keys', []), true)) {
            Cache::forget(config('cache-keys.theme_css_variables'));
        }

        // The brand layer memoises resolved values per request, so an explicit
        // forget() must drop that memo too - a write that bypasses the model
        // observer (media replacement, direct cache clear) would otherwise keep
        // serving the previous brand.
        Brand::clearCache();
    }

    protected static function resolveSingle(
        string $key,
        mixed $default,
        bool $skipCache = false,
    ): mixed {
        if (array_key_exists($key, self::$overrides)) {
            return self::$overrides[$key];
        }

        if (! $skipCache && array_key_exists($key, self::$runtime)) {
            return self::$runtime[$key] ?? $default;
        }

        $infoValue = self::resolveAppInfoValue($key);

        if ($infoValue !== null) {
            self::$runtime[$key] = $infoValue;

            return $infoValue;
        }

        if ($skipCache) {
            unset(self::$runtime[$key]);
            Cache::forget(config('cache-keys.settings_key').$key);

            // skipCache means "read the database". The per-key entry is now
            // derived from the shared settings map, so that map has to be
            // dropped as well or the bypass would serve the stale snapshot.
            Cache::forget(config('cache-keys.settings_all'));
        }

        try {
            $dbValue = Cache::rememberForever(
                config('cache-keys.settings_key').$key,
                function () use ($key) {
                    // Resolve from the shared settings map so a cold request
                    // costs one query for the whole table instead of one query
                    // per key. The map is hydrated through the model, so casts
                    // (encrypted, JSON, boolean) are already applied.
                    return self::all()->get($key) ?? self::NOT_FOUND_SENTINEL;
                },
            );
        } catch (QueryException $e) {
            self::logQuery('warning', 'Failed to resolve setting from database', $e, [
                'key' => $key,
            ]);

            $dbValue = self::NOT_FOUND_SENTINEL;
        }

        if ($dbValue !== self::NOT_FOUND_SENTINEL && ! is_null($dbValue)) {
            self::$runtime[$key] = $dbValue;

            return $dbValue;
        }

        if (config()->has($key)) {
            $configValue = config($key);
            self::$runtime[$key] = $configValue;

            return $configValue;
        }

        self::$runtime[$key] = $default;

        return $default;
    }

    protected static function resolveAppInfoValue(string $key): mixed
    {
        if (isset(self::$appInfoAliases[$key])) {
            return AppInfo::get(self::$appInfoAliases[$key]);
        }

        if (in_array($key, self::$appInfoKeys, true)) {
            return AppInfo::get($key);
        }

        return null;
    }
}
