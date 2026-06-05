<?php

declare(strict_types=1);

namespace App\SysAdmin\Setting\Support;

use App\Support\CacheKeys;
use App\Core\Support\SmartLogger;
use App\Support\Theme;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Centralized Application Metadata & Branding Service.
 *
 * S2 - Sustain: Single source of truth for all app metadata.
 * S3 - Scalable: Cached settings with fallback to Composer metadata.
 */
final class AppMetadata
{
    /**
     * Check if the application is installed via database.
     */
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

    /**
     * Safely resolve a setting value with automatic fallback on error.
     *
     * @template T
     *
     * @param callable(): T $resolver
     * @param T $fallback
     *
     * @return T
     */
    private static function withFallback(callable $resolver, mixed $fallback, string $context): mixed
    {
        try {
            return $resolver();
        } catch (Throwable $e) {
            self::logSettingsWarning($context, $e);

            return $fallback;
        }
    }

    /**
     * Log a warning for settings retrieval failures.
     */
    private static function logSettingsWarning(string $context, Throwable $e): void
    {
        SmartLogger::warning($context)
            ->withPayload(['error' => $e->getMessage()])
            ->systemOnly()
            ->save();
    }

    private static function resolveString(string $settingKey, string $fallback, string $context): string
    {
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

    /**
     * Get the core application name from Composer (SSoT).
     */
    public static function appName(): string
    {
        return AppInfo::get('name', 'Laravel');
    }

    /**
     * Get the dynamic brand name.
     * Returns Composer name if not installed, otherwise returns institution name from settings.
     */
    public static function brandName(): string
    {
        return self::resolveString('brand_name', self::appName(), 'Failed to get brand name from settings');
    }

    /**
     * Get the site title for browser tabs.
     */
    public static function siteTitle(): string
    {
        if (! self::isInstalled()) {
            return __('setup.wizard.page_title', ['app_name' => self::appName()]);
        }

        return self::resolveString('site_title', self::brandName(), 'Failed to get site title from settings');
    }

    /**
     * Get the core application logo URL.
     */
    public static function appLogo(): string
    {
        return asset('/brand/logo.png');
    }

    /**
     * Get the dynamic brand logo URL.
     */
    public static function brandLogo(): string
    {
        $default = self::appLogo();

        if (! self::isInstalled()) {
            return $default;
        }

        $logo = self::withFallback(fn () => Settings::get('brand_logo'), null, 'Failed to get brand logo from settings');

        return is_string($logo) && $logo !== '' ? $logo : $default;
    }

    /**
     * Get the site favicon URL.
     */
    public static function favicon(): string
    {
        $default = asset('/brand/favicon.ico');

        if (! self::isInstalled()) {
            return $default;
        }

        return self::withFallback(function () use ($default): string {
            $favicon = Settings::get('site_favicon');

            if (is_string($favicon) && $favicon !== '') {
                return $favicon;
            }

            $logo = Settings::get('brand_logo');

            return is_string($logo) && $logo !== '' ? $logo : $default;
        }, $default, 'Failed to get favicon from settings');
    }

    /**
     * Get branding colors.
     *
     * @return array<string, string>
     */
    public static function colors(): array
    {
        return self::withFallback(fn () => Theme::all(), Theme::defaults(), 'Failed to get branding colors from settings');
    }

    /**
     * Get the application version from Composer.
     */
    public static function version(): string
    {
        return AppInfo::version();
    }

    /**
     * Get the author name from Composer.
     */
    public static function authorName(): string
    {
        $author = AppInfo::get('author', []);

        return is_string($author['name'] ?? null) ? $author['name'] : '';
    }

    /**
     * Get the author email from Composer.
     */
    public static function authorEmail(): string
    {
        $author = AppInfo::get('author', []);

        return is_string($author['email'] ?? null) ? $author['email'] : '';
    }

    /**
     * Get the application description from Composer.
     */
    public static function description(): string
    {
        return AppInfo::get('description', '');
    }

    /**
     * Get the license from Composer.
     */
    public static function license(): string
    {
        return AppInfo::get('license', 'MIT');
    }

    /**
     * Get a value by key with fallback chain:
     * 1. If installed: check settings (branding)
     * 2. Fallback to AppInfo (Composer metadata)
     * 3. Use default parameter.
     */
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
