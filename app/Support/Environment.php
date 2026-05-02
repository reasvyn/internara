<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Utility class for environment and application runtime metadata.
 *
 * S1 - Secure: Centralized environment checks.
 * S2 - Sustain: Shared logic for environment detection.
 */
final class Environment
{
    /**
     * Determine if the application is currently in debug mode.
     */
    public static function isDebugMode(): bool
    {
        return (bool) config('app.debug', false);
    }

    /**
     * Determine if the application is running in a development environment.
     */
    public static function isDevelopment(): bool
    {
        return app()->environment('local', 'dev');
    }

    /**
     * Determine if the application is currently running tests.
     */
    public static function isTesting(): bool
    {
        return defined('PHPUNIT_COMPOSER_INSTALL') ||
            defined('__PEST__') ||
            app()->runningUnitTests() ||
            app()->environment('testing');
    }

    /**
     * Determine if the application is currently in maintenance mode.
     */
    public static function isMaintenance(): bool
    {
        return app()->isDownForMaintenance();
    }

    /**
     * Determine if the application is running in a production environment.
     */
    public static function isProduction(): bool
    {
        return config('app.env') === 'production';
    }
}
