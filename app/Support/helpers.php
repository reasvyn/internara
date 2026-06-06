<?php

declare(strict_types=1);

use App\Settings\Support\AppInfo;
use App\Settings\Support\AppMetadata;
use App\Settings\Support\Settings;

if (! function_exists('setting')) {
    /**
     * Get application settings.
     *
     * S1 - Secure: Centralized access to system configurations.
     * S2 - Sustain: Single API for reading settings.
     * S3 - Scalable: Leverages cached Settings.
     *
     * Note: Writing settings should use SetSettingAction directly from the calling module.
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

        return Settings::get($key, $default, $skipCache);
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
