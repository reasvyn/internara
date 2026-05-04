<?php

declare(strict_types=1);

namespace App\Domain\Core\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Centralized Application Metadata & Branding Service.
 *
 * S2 - Sustain: Single source of truth for all app metadata.
 * S3 - Scalable: Cached settings with fallback to Composer metadata.
 */
final class AppMetadata
{
    private const LOCK_FILE = '.installed';

    /**
     * Check if the application is installed.
     */
    private static function isInstalled(): bool
    {
        try {
            return File::exists(storage_path('app/'.self::LOCK_FILE));
        } catch (Throwable $e) {
            self::logSettingsWarning('Failed to check installation status', $e);

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
        Log::warning($context, [
            'error' => $e->getMessage(),
        ]);
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
        if (! self::isInstalled()) {
            return self::appName();
        }

        $value = self::withFallback(
            fn () => Settings::get('brand_name', self::appName()),
            self::appName(),
            'Failed to get brand name from settings',
        );

        return is_string($value) ? $value : self::appName();
    }

    /**
     * Get the site title for browser tabs.
     */
    public static function siteTitle(): string
    {
        if (! self::isInstalled()) {
            return self::appName().' - Setup';
        }

        $value = self::withFallback(
            fn (): mixed => Settings::get('site_title', self::brandName()),
            self::brandName(),
            'Failed to get site title from settings',
        );

        return is_string($value) ? $value : self::brandName();
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
        $defaultLogo = self::appLogo();

        if (! self::isInstalled()) {
            return $defaultLogo;
        }

        $logo = self::withFallback(
            fn () => Settings::get('brand_logo'),
            null,
            'Failed to get brand logo from settings',
        );

        return is_string($logo) && $logo !== '' ? $logo : $defaultLogo;
    }

    /**
     * Get the site favicon URL.
     */
    public static function favicon(): string
    {
        $defaultFavicon = asset('/brand/favicon.ico');

        if (! self::isInstalled()) {
            return $defaultFavicon;
        }

        return self::withFallback(
            function () use ($defaultFavicon): string {
                $favicon = Settings::get('site_favicon');

                if (is_string($favicon) && $favicon !== '') {
                    return $favicon;
                }

                $logo = Settings::get('brand_logo');

                return is_string($logo) && $logo !== '' ? $logo : $defaultFavicon;
            },
            $defaultFavicon,
            'Failed to get favicon from settings',
        );
    }

    /**
     * Get branding colors.
     *
     * @return array<string, string>
     */
    public static function colors(): array
    {
        $defaults = [
            'primary' => '#0ea5e9',
            'secondary' => '#64748b',
            'accent' => '#f59e0b',
        ];

        return self::withFallback(
            fn () => [
                'primary' => Settings::get('primary_color', $defaults['primary']),
                'secondary' => Settings::get('secondary_color', $defaults['secondary']),
                'accent' => Settings::get('accent_color', $defaults['accent']),
            ],
            $defaults,
            'Failed to get branding colors from settings',
        );
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
