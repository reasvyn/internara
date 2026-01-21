<?php

declare(strict_types=1);

namespace Modules\UI\Core;

use Closure;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\LivewireManager;
use Modules\UI\Core\Contracts\SlotManager as SlotManagerContract;
use Modules\UI\Core\Contracts\SlotRegistry as SlotRegistryContract;

/**
 * Handles the rendering of components registered in slots.
 * It depends on the SlotRegistry to fetch the components for a given slot
 * and then uses the appropriate rendering engine (Blade, Livewire, etc.).
 */
class SlotManager implements SlotManagerContract
{
    /**
     * The slot registry instance.
     */
    protected SlotRegistryContract $registry;

    /**
     * The view factory instance.
     */
    protected ViewFactory $viewFactory;

    /**
     * The Livewire manager instance.
     */
    protected LivewireManager $livewireManager;

    /**
     * Create a new SlotManager instance.
     */
    public function __construct(
        SlotRegistryContract $registry,
        ViewFactory $viewFactory,
        LivewireManager $livewireManager,
    ) {
        $this->registry = $registry;
        $this->viewFactory = $viewFactory;
        $this->livewireManager = $livewireManager;
    }

    /**
     * Render all registered components for a given slot.
     *
     * @param string $slot The name of the slot to render.
     *
     * @return string The rendered components.
     */
    public function render(string $slot): string
    {
        return collect($this->registry->getSlotsFor($slot))
            ->map(function ($item) use ($slot) {
                try {
                    $view = $item['view'];
                    $data = $item['data'];

                    if ($view instanceof Closure) {
                        return $view($data);
                    }

                    if ($view instanceof View) {
                        return $view->with($data)->render();
                    }

                    if (is_string($view) && Str::startsWith($view, 'livewire:')) {
                        $component = Str::after($view, 'livewire:');

                        return $this->livewireManager->mount($component, $data, uniqid($component));
                    }

                    if (is_string($view)) {
                        return $this->viewFactory->make($view, $data)->render();
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Slot Injection Error: Failed to render component ['.(is_string($item['view']) ? $item['view'] : 'Closure')."] in slot [{$slot}]. Error: {$e->getMessage()}", [
                        'exception' => $e,
                        'slot' => $slot,
                        'data' => $item['data'],
                    ]);

                    // Return a placeholder or empty string in production,
                    // maybe a small warning icon in local/debug mode?
                    if (config('app.debug')) {
                        return "<!-- Slot Render Error: {$item['view']} -->";
                    }
                }

                // Return empty string for unrenderable types to avoid errors.
                return '';
            })
            ->implode('');
    }
}
