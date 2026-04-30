<?php

declare(strict_types=1);

use App\Actions\Setting\SetSettingAction;
use App\Support\Settings;

if (! function_exists('setting')) {
    /**
     * Get or set application settings.
     *
     * S1 - Secure: Centralized access to system configurations.
     * S2 - Sustain: Single API for all setting access patterns.
     * S3 - Scalable: Leverages cached Settings with Action-oriented writes.
     *
     * @param string|array|null $key Setting key, array of key-value pairs to set, or null to get Settings instance
     * @param mixed $default Default value when getting a setting
     * @param bool $skipCache Deprecated parameter kept for backward compatibility
     */
    function setting(
        string|array|null $key = null,
        mixed $default = null,
        bool $skipCache = false,
    ): mixed {
        // Return Settings service instance for null key
        if ($key === null) {
            return app(Settings::class);
        }

        // Set multiple settings (associative array)
        if (is_array($key) && ! empty($key) && is_string(array_key_first($key))) {
            return app(SetSettingAction::class)->executeBatch($key);
        }

        // Get single setting value
        if (is_string($key)) {
            return Settings::get($key, $default);
        }

        return $default;
    }
}
