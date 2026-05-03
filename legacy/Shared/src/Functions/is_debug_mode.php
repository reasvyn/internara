<?php

declare(strict_types=1);

use Modules\Shared\Support\Environment;

if (! function_exists('is_debug_mode')) {
    /**
     * Determine if the application is currently in debug mode.
     *
     * Wrapper for Modules\Shared\Support\Environment::isDebugMode.
     */
    function is_debug_mode(): bool
    {
        return Environment::isDebugMode();
    }
}
