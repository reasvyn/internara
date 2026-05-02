<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\Setup\SetupService;

/**
 * Centalized Branding Service.
 * Handles dynamic switching between App Branding (Internara)
 * and Institution Branding (School/Company) based on installation state.
 *
 * S2 - Sustain: Logic for branding is isolated from layouts.
 */
final class Branding
{
    /**
     * Get the core application name (SSoT).
     */
    public static function appName(): string
    {
        return AppInfo::get('name', 'Internara');
    }

    /**
     * Get the dynamic brand name.
     * Returns "Internara" if not installed, otherwise returns the institution name.
     */
    public static function brandName(): string
    {
        if (! self::isInstalled()) {
            return self::appName();
        }

        return (string) Settings::get('brand_name', self::appName());
    }

    /**
     * Get the site title for browser tabs.
     */
    public static function siteTitle(): string
    {
        if (! self::isInstalled()) {
            return self::appName().' - Setup';
        }

        return (string) Settings::get('site_title', self::brandName());
    }

    /**
     * Get the brand logo URL.
     */
    public static function logo(): string
    {
        $defaultLogo = asset('/brand/logo.png');

        if (! self::isInstalled()) {
            return $defaultLogo;
        }

        $logo = Settings::get('brand_logo');

        return $logo ? (string) $logo : $defaultLogo;
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

        $favicon = Settings::get('site_favicon');

        if ($favicon) {
            return (string) $favicon;
        }

        // Fallback to logo if favicon is not set
        $logo = Settings::get('brand_logo');

        return $logo ? (string) $logo : $defaultFavicon;
    }

    /**
     * Get branding colors.
     */
    public static function colors(): array
    {
        return [
            'primary' => Settings::get('primary_color', '#0ea5e9'),
            'secondary' => Settings::get('secondary_color', '#64748b'),
            'accent' => Settings::get('accent_color', '#f59e0b'),
        ];
    }

    /**
     * Helper to check installation state without DI.
     */
    private static function isInstalled(): bool
    {
        return app(SetupService::class)->isInstalled();
    }
}
