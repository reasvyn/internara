<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

/**
 * Utility class for environment and application runtime metadata.
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
        return config('app.env') === 'local' || config('app.env') === 'dev';
    }

    /**
     * Determine if the application is currently running tests.
     */
    public static function isTesting(): bool
    {
        return config('app.env') === 'testing' || app()->runningUnitTests();
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
