<?php

declare(strict_types=1);

namespace Modules\UI\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Modules\UI\Core\Contracts\SlotManager as SlotManagerContract;
use Modules\UI\Core\Contracts\SlotRegistry as SlotRegistryContract;
use Modules\UI\Core\SlotManager;
use Modules\UI\Core\SlotRegistry;
use Nwidart\Modules\Traits\PathNamespace;

class UIServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'UI';

    protected string $nameLower = 'ui';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();

        // Register Livewire components
        \Livewire\Livewire::component(
            'ui::notification-bridge',
            \Modules\UI\Livewire\NotificationBridge::class,
        );

        // Register anonymous components
        Blade::anonymousComponentPath(module_path('UI', 'resources/views/components'), 'ui');

        // Register class-based components
        Blade::component('ui::user-menu', \Modules\UI\View\Components\UserMenu::class);

        // Register the custom Blade directive
        Blade::directive('slotRender', function ($expression) {
            return "<?php echo \Modules\UI\Facades\SlotManager::render({$expression}); ?>";
        });
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerModule();
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Get the service bindings for the module.
     *
     * @return array<string, string|\Closure>
     */
    protected function bindings(): array
    {
        return [
            SlotManagerContract::class => SlotManager::class,
            SlotRegistryContract::class => SlotRegistry::class,
            \Modules\UI\Services\Contracts\LocalizationService::class =>
                \Modules\UI\Services\LocalizationService::class,
        ];
    }

    protected function viewSlots(): array
    {
        return [
            'navbar.brand' => 'ui::brand',
            'navbar.actions' => [
                'livewire:ui::language-switcher',
                'ui::user-menu',
                'ui::theme-toggle',
            ],
            'footer.app-credit' => 'ui::app-credit',
        ];
    }
}
