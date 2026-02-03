<?php

declare(strict_types=1);

if (! function_exists('slotExists')) {
    /**
     * Check if a UI slot has any registered components.
     */
    function slotExists(string $name): bool
    {
        return \Modules\UI\Facades\SlotRegistry::hasSlot($name);
    }
}
