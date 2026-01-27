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
        ];
    }

    protected function viewSlots(): array
    {
        return [
            'navbar.brand' => 'ui::components.brand',
            'navbar.actions' => [
                'livewire:ui::language-switcher',
                'ui::components.user-menu',
                'ui::components.theme-toggle',
            ],
            'footer.app-credit' => 'ui::components.app-credit',
        ];
    }
}
