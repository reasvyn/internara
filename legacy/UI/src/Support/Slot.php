<?php

declare(strict_types=1);

namespace Modules\UI\Support;

use Modules\UI\Facades\SlotRegistry;

/**
 * Utility class for UI Slot operations.
 */
final class Slot
{
    /**
     * Determines if a specific UI slot has any registered components or content.
     *
     * @param string $name The name of the slot to check.
     */
    public static function exists(string $name): bool
    {
        return SlotRegistry::hasSlot($name);
    }
}
