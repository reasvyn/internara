<?php

declare(strict_types=1);

use Modules\UI\Support\Slot;

if (! function_exists('slot_exists')) {
    /**
     * Global wrapper for Modules\UI\Support\Slot::exists.
     */
    function slot_exists(string $name): bool
    {
        return Slot::exists($name);
    }
}
