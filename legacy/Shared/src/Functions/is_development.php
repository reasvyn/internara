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
