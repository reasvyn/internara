declare(strict_types=1);

use App\Domain\Admin\Actions\SetSettingAction;
use App\Domain\Core\Support\Environment;
use App\Domain\Core\Support\Settings;

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
     * @param bool $skipCache Whether to skip the cache and read from database
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
            return Settings::get($key, $default, $skipCache);
        }

        return $default;
    }
}

if (! function_exists('is_debug_mode')) {
    /**
     * Determine if the application is currently in debug mode.
     */
    function is_debug_mode(): bool
    {
        return Environment::isDebugMode();
    }
}

if (! function_exists('is_development')) {
    /**
     * Determine if the application is running in a development environment.
     */
    function is_development(): bool
    {
        return Environment::isDevelopment();
    }
}

if (! function_exists('is_testing')) {
    /**
     * Determine if the application is currently running tests.
     */
    function is_testing(): bool
    {
        return Environment::isTesting();
    }
}

if (! function_exists('is_maintenance')) {
    /**
     * Determine if the application is currently in maintenance mode.
     */
    function is_maintenance(): bool
    {
        return Environment::isMaintenance();
    }
}

if (! function_exists('brand')) {
    /**
     * Get dynamic branding values with OOP pattern.
     *
     * S2 - Sustain: Single API for dynamic branding access.
     * S3 - Scalable: Falls back from settings to Composer metadata.
     *
     * @param string $key Brand key: name, logo, favicon, site_title, colors, version, author_name, author_email, description, license
     * @param mixed $default Default value when key is not found
     */
    function brand(string $key, mixed $default = null): mixed
    {
        return app(AppMetadata::class)->get($key, $default);
    }
}

if (! function_exists('app_info')) {
    /**
     * Get application metadata from Composer (SSoT).
     *
     * S2 - Sustain: Centralized access to Composer metadata.
     *
     * @param string|null $key Metadata key: name, version, description, author, support, license
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
