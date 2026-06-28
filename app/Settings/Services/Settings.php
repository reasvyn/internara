<?php

declare(strict_types=1);

namespace App\Settings\Services;

use App\Core\Support\AppInfo;
use App\Core\Support\SmartLogger;
use App\Settings\Data\SettingData;
use App\Settings\Events\SettingUpdated;
use App\Settings\Models\Setting;
use App\Settings\Rules\ValidSettingKey;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;

final class Settings
{
    protected static array $overrides = [];

    protected static array $appInfoKeys = ['name', 'version', 'support', 'license'];

    protected static array $appInfoAliases = [
        'app_name' => 'name',
        'app_version' => 'version',
        'app_support' => 'support',
        'app_license' => 'license',
        'author' => 'author.name',
    ];

    private static function logQuery(string $level, string $message, QueryException $e, array $context = []): void
    {
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
                fn () => Setting::all()->pluck('value', 'key'),
            );
        } catch (QueryException $e) {
            self::logQuery('error', 'Failed to fetch all settings from database', $e);

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
            self::logQuery('error', 'Failed to fetch settings group from database', $e, [
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
                Event::dispatch(new SettingUpdated(
                    setting: new SettingData(
                        key: $key,
                        value: $value,
                        type: $model->type,
                        group: $model->group,
                    ),
                    wasRecentlyCreated: $model->wasRecentlyCreated,
                ));

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
            self::logQuery('warning', 'Failed to check settings group existence', $e, [
                'group' => $name,
            ]);

            return false;
        }
    }

    public static function forgetGroup(string $name): void
    {
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
            self::logQuery('error', 'Failed to count settings by group', $e);

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
            Cache::forget(config('cache-keys.settings_key').$key);
        }

        try {
            $dbValue = Cache::rememberForever(config('cache-keys.settings_key').$key, function () use (
                $key,
            ) {
                $setting = Setting::where('key', $key)->first();

                return $setting?->value;
            });
        } catch (QueryException $e) {
            self::logQuery('warning', 'Failed to resolve setting from database', $e, [
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
        if (isset(self::$appInfoAliases[$key])) {
            return AppInfo::get(self::$appInfoAliases[$key]);
        }

        if (in_array($key, self::$appInfoKeys, true)) {
            return AppInfo::get($key);
        }

        return null;
    }
}
