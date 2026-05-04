<?php

declare(strict_types=1);

use App\Domain\Core\Support\AppInfo;
use App\Domain\Core\Support\AppMetadata;
use App\Domain\Core\Support\Environment;
use App\Domain\Core\Support\Settings;

if (! function_exists('setting')) {
    /**
     * Get application settings.
     *
     * S1 - Secure: Centralized access to system configurations.
     * S2 - Sustain: Single API for reading settings.
     * S3 - Scalable: Leverages cached Settings.
     *
     * Note: Writing settings should use SetSettingAction directly from the calling domain.
     *
     * @param string|array|null $key Setting key, or null to get Settings instance
     * @param mixed $default Default value when getting a setting
     * @param bool $skipCache Whether to skip the cache and read from database
     */
    function setting(
        string|array|null $key = null,
        mixed $default = null,
        bool $skipCache = false,
    ): mixed {
        if ($key === null) {
            return app(Settings::class);
        }

        if (is_string($key)) {
            return Settings::get($key, $default, $skipCache);
        }

        return $default;
    }
}

if (! function_exists('is_debug_mode')) {
    function is_debug_mode(): bool
    {
        return Environment::isDebugMode();
    }
}

if (! function_exists('is_development')) {
    function is_development(): bool
    {
        return Environment::isDevelopment();
    }
}

if (! function_exists('is_testing')) {
    function is_testing(): bool
    {
        return Environment::isTesting();
    }
}

if (! function_exists('is_maintenance')) {
    function is_maintenance(): bool
    {
        return Environment::isMaintenance();
    }
}

if (! function_exists('brand')) {
    /**
     * Get dynamic branding values.
     *
     * S2 - Sustain: Single API for dynamic branding access.
     * S3 - Scalable: Falls back from settings to Composer metadata.
     *
     * @param string $key Brand key (name, logo, favicon, site_title, colors, version, etc.)
     * @param mixed $default Default value when key is not found
     */
    function brand(string $key, mixed $default = null): mixed
    {
        return AppMetadata::get($key, $default);
    }
}

if (! function_exists('app_info')) {
    /**
     * Get application metadata from Composer (SSoT).
     *
     * S2 - Sustain: Centralized access to Composer metadata.
     *
     * @param string|null $key Metadata key (name, version, author, etc.)
     * @param mixed $default Default value when key is not found
     */
    function app_info(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return AppInfo::all();
        }

        return AppInfo::get($key, $default);
    }
}
