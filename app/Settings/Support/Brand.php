<?php

declare(strict_types=1);

namespace App\Settings\Support;

use App\Core\Support\AppInfo;
use App\Core\Support\SmartLogger;
use App\Settings\Models\Setting as SettingModel;
use App\Settings\Theme\Support\Theme;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Throwable;

final class Brand
{
    private static function withFallback(
        callable $resolver,
        mixed $fallback,
        string $context,
    ): mixed {
        try {
            return $resolver();
        } catch (Throwable $e) {
            self::logSettingsWarning($context, $e);

            return $fallback;
        }
    }

    private static function logSettingsWarning(string $context, Throwable $e): void
    {
        SmartLogger::warning($context)
            ->withPayload(['error' => $e->getMessage()])
            ->systemOnly()
            ->save();
    }

    private static function resolveString(
        string $settingKey,
        string $fallback,
        string $context,
    ): string {
        $value = self::withFallback(
            fn () => SettingModel::where('key', $settingKey)->value('value'),
            null,
            $context,
        );

        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    public static function clearCache(): void
    {
        Cache::forget(config('cache-keys.brand_colors'));
    }

    public static function name(): string
    {
        return self::resolveString(
            'name',
            AppInfo::name(),
            'Failed to get name from settings',
        );
    }

    public static function title(): string
    {
        return self::resolveString(
            'title',
            self::name(),
            'Failed to get site title from settings',
        );
    }

    public static function logo(): string
    {
        $default = Config::get('app.logo', asset('/logo.png'));

        $logo = self::withFallback(
            fn () => SettingModel::where('key', 'logo')->value('value'),
            null,
            'Failed to get logo from settings',
        );

        return is_string($logo) && $logo !== '' ? $logo : $default;
    }

    public static function favicon(): string
    {
        $default = Config::get('app.favicon', asset('/favicon.ico'));

        $favicon = self::withFallback(
            fn () => SettingModel::where('key', 'favicon')->value('value'),
            null,
            'Failed to get favicon from settings',
        );

        return is_string($favicon) && $favicon !== '' ? $favicon : $default;
    }

    public static function colors(): array
    {
        return Cache::remember(config('cache-keys.brand_colors'), 86400, function () {
            return self::withFallback(
                fn () => Theme::all(),
                Theme::defaults(),
                'Failed to get branding colors from settings',
            );
        });
    }

    public static function version(): string
    {
        return AppInfo::version();
    }

    public static function authorName(): string
    {
        return AppInfo::authorName();
    }

    public static function authorEmail(): string
    {
        return AppInfo::authorEmail();
    }

    public static function description(): string
    {
        return AppInfo::description();
    }

    public static function license(): string
    {
        return AppInfo::license();
    }

    public static function gitUrl(): string
    {
        return AppInfo::gitUrl();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $mapping = [
            'name' => fn () => self::name(),
            'title' => fn () => self::title(),
            'logo' => fn () => self::logo(),
            'favicon' => fn () => self::favicon(),
            'colors' => fn () => self::colors(),
            'version' => fn () => self::version(),
            'author_name' => fn () => self::authorName(),
            'author_email' => fn () => self::authorEmail(),
            'description' => fn () => self::description(),
            'license' => fn () => self::license(),
            'gitUrl' => fn () => self::gitUrl(),
        ];

        if (array_key_exists($key, $mapping)) {
            return $mapping[$key]();
        }

        return AppInfo::get($key, $default);
    }
}
