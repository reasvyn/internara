<?php

declare(strict_types=1);

use App\Modules\Setting\Services\Settings;
use App\Modules\Setting\Support\Brand;

if (! function_exists('settings')) {
    /**
     * Get application setting.
     *
     * S1 - Secure: Centralized access to system configurations.
     * S2 - Sustain: Single API for reading setting.
     * S3 - Scalable: Leverages cached Setting.
     *
     * Note: Writing setting should use SetSettingAction directly from the calling module.
     *
     * @param string|array|null $key Setting key, or null to get Setting instance
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
     * Get dynamic branding values from database with config fallback.
     *
     * S2 - Sustain: Single API for dynamic branding access.
     * S3 - Scalable: Falls back from database to config to AppInfo.
     *
     * @param string $key Brand key (name, logo, favicon, site_title, colors, version, etc.)
     * @param mixed $default Default value when key is not found
     */
    function brand(string $key, mixed $default = null): mixed
    {
        return Brand::get($key, $default);
    }
}

if (! function_exists('feature')) {
    /**
     * Resolve a feature flag through the setting resolution chain and cast to bool.
     *
     * Spec: YB22J FR-SET-017 / 6.1
     *
     * @param string $key Feature flag key (without 'features.' prefix)
     * @param bool $default Default value when flag is not set
     */
    function feature(string $key, bool $default = false): bool
    {
        return (bool) setting("features.{$key}", $default);
    }
}
