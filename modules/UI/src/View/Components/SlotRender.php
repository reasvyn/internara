<?php

declare(strict_types=1);

namespace Modules\UI\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Modules\UI\Facades\SlotManager;

/**
 * @title Slot Render Component
 *
 * This component is responsible for rendering content registered in a named slot
 * via the SlotManager. It acts as a placeholder in Blade views where dynamic
 * UI elements, registered by other modules, should be displayed.
 */
class SlotRender extends Component
{
    /**
     * Create a new component instance.
     *
     * @param string $name The name of the slot to render.
     */
    public function __construct(protected readonly string $name) {}

    /**
     * Get the view/contents that represent the component.
     */
    public function render(): View|string
    {
        return SlotManager::render($this->name);
    }
}
