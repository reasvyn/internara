<?php

declare(strict_types=1);

namespace Modules\UI\Core\Navigation;

use Modules\UI\Facades\SlotRegistry;

/**
 * Class SidebarRegistry
 *
 * Centralized registry for all management sidebar menu items.
 * This class keeps ServiceProviders thin and provides a single SSoT for navigation.
 */
class SidebarRegistry
{
    /**
     * Register all sidebar menu items into the SlotRegistry.
     */
    public static function register(): void
    {
        SlotRegistry::configure([
            'sidebar.menu' => config('ui.sidebar.menu', []),
        ]);
    }
}
