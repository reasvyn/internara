<?php

declare(strict_types=1);

namespace App\Settings\Support;

use App\Core\Support\CacheKeys;
use App\Core\Support\SmartLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

final class AppMetadata
{
    private static function isInstalled(): bool
    {
        try {
            return (bool) Cache::rememberForever(CacheKeys::SETUP_INSTALLED, function () {
                return DB::table('setups')->value('is_installed');
            });
        } catch (Throwable $e) {
            return false;
        }
    }

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
        if (! self::isInstalled()) {
            return $fallback;
        }

        $value = self::withFallback(
            fn () => Settings::get($settingKey, $fallback),
            $fallback,
            $context,
        );

        return is_string($value) ? $value : $fallback;
    }

    public static function appName(): string
    {
        return AppInfo::get('name', 'Laravel');
    }

    public static function brandName(): string
    {
        return self::resolveString(
            'brand_name',
            self::appName(),
            'Failed to get brand name from settings',
        );
    }

    public static function siteTitle(): string
    {
        if (! self::isInstalled()) {
            return __('setup.wizard.page_title', ['app_name' => self::appName()]);
        }

        return self::resolveString(
            'site_title',
            self::brandName(),
            'Failed to get site title from settings',
        );
    }

    public static function appLogo(): string
    {
        return asset('/brand/logo.png');
    }

    public static function brandLogo(): string
    {
        $default = self::appLogo();

        if (! self::isInstalled()) {
            return $default;
        }

        $logo = self::withFallback(
            fn () => Settings::get('brand_logo'),
            null,
            'Failed to get brand logo from settings',
        );

        return is_string($logo) && $logo !== '' ? $logo : $default;
    }

    public static function favicon(): string
    {
        $default = asset('/brand/favicon.ico');

        if (! self::isInstalled()) {
            return $default;
        }

        return self::withFallback(
            function () use ($default): string {
                $favicon = Settings::get('site_favicon');

                if (is_string($favicon) && $favicon !== '') {
                    return $favicon;
                }

                $logo = Settings::get('brand_logo');

                return is_string($logo) && $logo !== '' ? $logo : $default;
            },
            $default,
            'Failed to get favicon from settings',
        );
    }

    public static function colors(): array
    {
        return self::withFallback(
            fn () => Theme::all(),
            Theme::defaults(),
            'Failed to get branding colors from settings',
        );
    }

    public static function version(): string
    {
        return AppInfo::version();
    }

    public static function authorName(): string
    {
        $author = AppInfo::get('author', []);

        return is_string($author['name'] ?? null) ? $author['name'] : '';
    }

    public static function authorEmail(): string
    {
        $author = AppInfo::get('author', []);

        return is_string($author['email'] ?? null) ? $author['email'] : '';
    }

    public static function description(): string
    {
        return AppInfo::get('description', '');
    }

    public static function license(): string
    {
        return AppInfo::get('license', 'MIT');
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $mapping = [
            'name' => fn () => self::brandName(),
            'app_name' => fn () => self::appName(),
            'logo' => fn () => self::brandLogo(),
            'app_logo' => fn () => self::appLogo(),
            'favicon' => fn () => self::favicon(),
            'site_title' => fn () => self::siteTitle(),
            'colors' => fn () => self::colors(),
            'version' => fn () => self::version(),
            'author_name' => fn () => self::authorName(),
            'author_email' => fn () => self::authorEmail(),
            'description' => fn () => self::description(),
            'license' => fn () => self::license(),
        ];

        if (array_key_exists($key, $mapping)) {
            return $mapping[$key]();
        }

        return AppInfo::get($key, $default);
    }
}
