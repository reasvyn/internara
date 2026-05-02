<?php

declare(strict_types=1);

use Modules\Shared\Support\Environment;

if (! function_exists('is_testing')) {
    /**
     * Determine if the application is currently running tests.
     */
    function is_testing(): bool
    {
        return Environment::isTesting();
    }
}
