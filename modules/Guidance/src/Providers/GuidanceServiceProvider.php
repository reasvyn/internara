<?php

declare(strict_types=1);

namespace Modules\Guidance\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class GuidanceServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Guidance';

    protected string $nameLower = 'guidance';

    /**
     * The policy mappings for the module.
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        \Modules\Guidance\Models\Handbook::class => \Modules\Guidance\Policies\HandbookPolicy::class,
    ];

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();
    }

    /**
     * Define view slots for UI injection.
     */
    protected function viewSlots(): array
    {
        try {
            $settingService = $this->app->make(
                \Modules\Setting\Services\Contracts\SettingService::class,
            );

            if (! $settingService->getValue('feature_guidance_enabled', true)) {
                return [];
            }
        } catch (\Throwable $e) {
            // Fallback for cases where database/cache is not ready (e.g. during boot in tests)
            return [];
        }

        return [
            'student.dashboard.active-content' => [
                'guidance::livewire.handbook-hub' => [
                    'order' => 10,
                ],
            ],
            'student.dashboard.requirements' => [
                'guidance::livewire.acknowledgement-modal' => [
                    'order' => 5,
                ],
            ],
            'sidebar.menu' => [
                'ui::menu-item#guidance' => [
                    'title' => __('Manajemen Panduan'),
                    'icon' => 'tabler.books',
                    'link' => '/guidance/manage',
                    'order' => 61,
                ],
            ],
        ];
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerBindings();
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
            \Modules\Guidance\Services\Contracts\HandbookService::class => \Modules\Guidance\Services\HandbookService::class,
        ];
    }
}
