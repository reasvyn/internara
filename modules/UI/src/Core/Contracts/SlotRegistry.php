<?php

declare(strict_types=1);

namespace Modules\UI\Core\Contracts;

use Closure;
use Illuminate\Contracts\View\View;

interface SlotRegistry
{
    /**
     * Configure the slot registry with an initial set of slots.
     */
    public function configure(array $slots = []): void;

    /**
     * Register a renderable component for a given slot.
     *
     * @param string $slot The name of the slot.
     * @param string|Closure|View $view The component to render. Can be a view name, a Livewire component name, a Closure, or a View object.
     * @param array $data Optional data to pass to the component.
     */
    public function register(string $slot, string|Closure|View $view, array $data = []): void;

    /**
     * Get all registered components for a given slot.
     *
     * @param string $slot The name of the slot.
     *
     * @return array The registered components.
     */
    public function getSlotsFor(string $slot): array;

    /**
     * Check if a slot has any registered components.
     */
    public function hasSlot(string $slot): bool;
}
