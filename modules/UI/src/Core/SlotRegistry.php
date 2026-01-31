<?php

declare(strict_types=1);

namespace Modules\UI\Core;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Modules\UI\Core\Contracts\SlotRegistry as SlotRegistryContract;

/**
 * Handles the registration of UI components into named slots.
 * This class is intended to be used as a singleton to collect registrations
 * from various parts of the application.
 */
class SlotRegistry implements SlotRegistryContract
{
    /**
     * The array of registered slots.
     */
    protected array $slots = [];

    public function configure(array $slots = []): void
    {
        foreach ($slots as $slot => $components) {
            if (is_array($components)) {
                if (Arr::isList($components)) {
                    // ['slot' => ['component1', 'component2']]
                    foreach ($components as $component) {
                        $this->register($slot, $component);
                    }
                } else {
                    // ['slot' => ['component' => [data]]]
                    foreach ($components as $component => $data) {
                        $this->register($slot, $component, $data);
                    }
                }
            } else {
                // ['slot' => 'component]
                $this->register($slot, $components);
            }
        }
    }

    /**
     * Register a renderable component for a given slot.
     *
     * @param string $slot The name of the slot.
     * @param string|Closure|View $view The component to render.
     * @param array $data Optional data to pass to the component.
     */
    public function register(string $slot, string|Closure|View $view, array $data = []): void
    {
        // Strip unique suffix if present (e.g., ui::component#unique-id)
        if (is_string($view) && str_contains($view, '#')) {
            $view = explode('#', $view)[0];
        }

        // Trace and Log potential invalid component names (e.g. containing colon outside of namespace)
        if (
            is_string($view) &&
            str_contains($view, ':') &&
            ! str_contains($view, '::') &&
            ! str_starts_with($view, 'livewire:')
        ) {
            Log::warning(
                "Slot Injection: Registering potentially invalid component name [{$view}] into slot [{$slot}]. Ensure it's a valid Blade component or view alias.",
            );
        }

        $this->slots[$slot][] = [
            'view' => $view,
            'data' => $data,
        ];
    }

    /**
     * Get all registered components for a given slot, sorted by order.
     *
     * @param string $slot The name of the slot.
     *
     * @return array The registered components.
     */
    public function getSlotsFor(string $slot): array
    {
        return collect($this->slots[$slot] ?? [])
            ->sortBy(function ($item) {
                return $item['data']['order'] ?? 100;
            })
            ->values()
            ->toArray();
    }
}
