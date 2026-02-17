<?php

declare(strict_types=1);

use Modules\Shared\Support\Environment;

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
