<?php

declare(strict_types=1);

namespace App\Settings\Support;

use App\Core\Support\AppInfo as CoreAppInfo;
use Illuminate\Support\Facades\Cache;

final class AppInfo
{
    public static function all(): array
    {
        CoreAppInfo::all();

        return CoreAppInfo::all();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return CoreAppInfo::get($key, $default);
    }

    public static function version(): string
    {
        return CoreAppInfo::version();
    }

    public static function author(): array
    {
        return CoreAppInfo::author();
    }

    public static function logo(): string
    {
        return CoreAppInfo::logo();
    }

    public static function gitUrl(): string
    {
        return CoreAppInfo::gitUrl();
    }

    public static function clearCache(): void
    {
        CoreAppInfo::clearCache();
        Cache::forget(config('cache-keys.appinfo_metadata'));
    }
}
