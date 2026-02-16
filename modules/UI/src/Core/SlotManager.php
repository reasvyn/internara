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
                $view = $item['view'];
                $data = $item['data'];

                // Ensure $attributes is always defined for anonymous components rendered as views.
                $data = array_merge(
                    ['attributes' => new \Illuminate\View\ComponentAttributeBag([])],
                    $data,
                );

                try {
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
                        return \Illuminate\Support\Facades\Blade::render("<x-{$view} />", $data);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error(
                        'Slot Injection Error: Failed to render component [' .
                            (is_string($view) ? $view : 'Closure') .
                            "] in slot [{$slot}]. Error: {$e->getMessage()}",
                        [
                            'exception' => $e,
                            'slot' => $slot,
                            'data' => $item['data'],
                        ],
                    );

                    // If we are in local/debug mode, we let the exception bubble up
                    // to prevent corrupted component stacks and provide better debugging.
                    if (config('app.debug')) {
                        throw $e;
                    }
                }

                // Return empty string for unrenderable types or failed renders in production.
                return '';
            })
            ->implode('');
    }
}
