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
     * Render registered components for a given slot with optional filtering.
     *
     * @param string $slot The name of the slot to render.
     * @param array $options Optional rendering options (e.g., 'filter' => 'view-name').
     *
     * @return string The rendered components.
     */
    public function render(string $slot, array $options = []): string
    {
        return collect($this->registry->getSlotsFor($slot))
            ->filter(function ($item) use ($options) {
                $view = $item['view'];
                $data = $item['data'];
                $user = auth()->user();

                // 1. Check for specific filter
                if (isset($options['filter']) && $view !== $options['filter']) {
                    return false;
                }

                // 2. Check for specific permissions
                if (isset($data['permission']) && ! $user?->can($data['permission'])) {
                    return false;
                }

                // 2. Check for required roles (e.g., 'admin|super-admin')
                if (isset($data['role'])) {
                    $roles = explode('|', $data['role']);
                    if (! $user?->hasRole($roles)) {
                        return false;
                    }
                }

                return true;
            })
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
                        $stableId = md5($slot.$component);

                        return $this->livewireManager->mount($component, $data, $stableId);
                    }

                    if (is_string($view)) {
                        $componentName = Str::before($view, '#');

                        return \Illuminate\Support\Facades\Blade::render(
                            '<x-dynamic-component :component="$component" {{ $attributes }} />',
                            [
                                'component' => $componentName,
                                'attributes' => new \Illuminate\View\ComponentAttributeBag($data),
                            ],
                        );
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error(
                        'Slot Injection Error: Failed to render component ['.
                            (is_string($view) ? $view : 'Closure').
                            "] in slot [{$slot}]. Error: {$e->getMessage()}",
                        [
                            'exception' => $e,
                            'slot' => $slot,
                            'data' => $item['data'],
                        ],
                    );

                    // If we are in local/debug mode, we let the exception bubble up
                    // to prevent corrupted component stacks and provide better debugging.
                    if (is_debug_mode()) {
                        throw $e;
                    }
                }

                // Return empty string for unrenderable types or failed renders in production.
                return '';
            })
            ->implode('');
    }
}
