<?php

declare(strict_types=1);

use Modules\Shared\Support\Module;

if (! function_exists('is_active_module')) {
    /**
     * Global wrapper for Modules\Shared\Support\Module::isActive.
     */
    function is_active_module(string $name): bool
    {
        return Module::isActive($name);
    }
}
