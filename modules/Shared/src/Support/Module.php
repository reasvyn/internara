<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Nwidart\Modules\Facades\Module as NwidartModule;

/**
 * Utility class for module-related operations.
 */
final class Module
{
    /**
     * Determines if a specific module is currently enabled in the ecosystem.
     *
     * @param string $name The name of the module to check.
     */
    public static function isActive(string $name): bool
    {
        return NwidartModule::isEnabled($name);
    }
}
