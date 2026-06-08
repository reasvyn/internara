<?php

declare(strict_types=1);

namespace App\Settings\Support;

use App\Core\Support\CacheKeys;
use App\Core\Support\SmartLogger;
use App\Settings\Models\Setting;
use App\Settings\Rules\ValidSettingKey;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

final class Settings
{
    protected static array $overrides = [];

    protected static array $appInfoMap = [
        'app_name' => 'name',
        'app_version' => 'version',
        'app_author' => 'author.name',
        'app_support' => 'support',
        'app_license' => 'license',
    ];

    protected static function logQueryError(
        string $message,
        QueryException $e,
        array $context = [],
    ): void {
        SmartLogger::error($message)
            ->withPayload(array_merge($context, ['error' => $e->getMessage()]))
            ->systemOnly()
            ->save();
    }

    protected static function logQueryWarning(
        string $message,
        QueryException $e,
        array $context = [],
    ): void {
        SmartLogger::warning($message)
            ->withPayload(array_merge($context, ['error' => $e->getMessage()]))
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
            Cache::forget(CacheKeys::SETTINGS_ALL);
        }

        try {
            return Cache::rememberForever(
                CacheKeys::SETTINGS_ALL,
                fn () => Setting::all()->pluck('value', 'key'),
            );
        } catch (QueryException $e) {
            self::logQueryError('Failed to fetch all settings from database', $e);

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
            Cache::forget(CacheKeys::SETTINGS_GROUP.$name);
        }

        try {
            return Cache::rememberForever(
                CacheKeys::SETTINGS_GROUP.$name,
                fn () => Setting::group($name)->get(),
            );
        } catch (QueryException $e) {
            self::logQueryError('Failed to fetch settings group from database', $e, [
                'group' => $name,
            ]);

            return collect();
        }
    }

    public static function override(array $overrides): void
    {
        self::$overrides = array_merge(self::$overrides, $overrides);
    }

    public static function clearOverrides(): void
    {
        self::$overrides = [];
    }

    public static function set(array $settings): int
    {
        $updated = 0;

        foreach ($settings as $key => $attributes) {
            Validator::validate(
                ['key' => $key],
                [
                    'key' => ['required', new ValidSettingKey],
                ],
            );
            $value = is_array($attributes) ? $attributes['value'] ?? null : $attributes;
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

    public static function forgetGroup(string $name): void
    {
        Cache::forget(CacheKeys::SETTINGS_GROUP.$name);
        Cache::forget(CacheKeys::SETTINGS_ALL);
        Cache::forget(CacheKeys::THEME_CSS_VARIABLES);

        try {
            $keys = Setting::group($name)->pluck('key');

            foreach ($keys as $key) {
                Cache::forget(CacheKeys::SETTINGS_KEY.$key);
            }
        } catch (QueryException $e) {
            self::logQueryWarning('Failed to forget group cache keys', $e, [
                'group' => $name,
            ]);
        }
    }

    public static function keys(bool $skipCache = false): Collection
    {
        if ($skipCache) {
            Cache::forget(CacheKeys::SETTINGS_KEYS);
        }

        try {
            return Cache::rememberForever(
                CacheKeys::SETTINGS_KEYS,
                fn () => Setting::query()->orderBy('key')->pluck('key'),
            );
        } catch (QueryException $e) {
            self::logQueryError('Failed to fetch setting keys', $e);

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
            self::logQueryError('Failed to count settings by group', $e);

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
            self::logQueryWarning('Failed to fetch setting groups', $e);

            return collect();
        }
    }

    public static function forget(string $key, ?string $group = null): void
    {
        Cache::forget(CacheKeys::SETTINGS_KEY.$key);

        if ($group !== null) {
            Cache::forget(CacheKeys::SETTINGS_GROUP.$group);
        } else {
            try {
                $setting = Setting::where('key', $key)->first();

                if ($setting?->group) {
                    Cache::forget(CacheKeys::SETTINGS_GROUP.$setting->group);
                }
            } catch (QueryException $e) {
                self::logQueryWarning('Failed to invalidate setting group cache', $e, [
                    'key' => $key,
                ]);
            }
        }

        Cache::forget(CacheKeys::SETTINGS_KEY.'all');

        if (
            in_array(
                $key,
                [
                    'primary_color',
                    'secondary_color',
                    'accent_color',
                    'base_color',
                    'brand_logo',
                    'brand_logo_ref',
                    'site_favicon',
                    'brand_favicon_ref',
                ],
                true,
            )
        ) {
            Cache::forget(CacheKeys::THEME_CSS_VARIABLES);
        }
    }

    protected static function resolveSingle(
        string $key,
        mixed $default,
        bool $skipCache = false,
    ): mixed {
        if (array_key_exists($key, self::$overrides)) {
            return self::$overrides[$key];
        }

        $infoValue = self::resolveAppInfoValue($key);

        if ($infoValue !== null) {
            return $infoValue;
        }

        if ($skipCache) {
            Cache::forget(CacheKeys::SETTINGS_KEY.$key);
        }

        try {
            $dbValue = Cache::rememberForever(CacheKeys::SETTINGS_KEY.$key, function () use (
                $key,
            ) {
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

        if (config()->has($key)) {
            return config($key);
        }

        return $default;
    }

    protected static function resolveAppInfoValue(string $key): mixed
    {
        if (! isset(self::$appInfoMap[$key])) {
            return null;
        }

        return AppInfo::get(self::$appInfoMap[$key]);
    }
}
