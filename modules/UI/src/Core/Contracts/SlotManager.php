<?php

declare(strict_types=1);

namespace Modules\UI\Core\Contracts;

interface SlotManager
{
    /**
     * Render registered components for a given slot with optional filtering.
     *
     * @param string $slot The name of the slot to render.
     * @param array $options Optional rendering options.
     *
     * @return string The rendered components.
     */
    public function render(string $slot, array $options = []): string;
}
