<?php

declare(strict_types=1);

use Modules\Shared\Support\Environment;

if (! function_exists('is_maintenance')) {
    /**
     * Determine if the application is currently in maintenance mode.
     */
    function is_maintenance(): bool
    {
        return Environment::isMaintenance();
    }
}
