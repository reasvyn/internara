<?php

declare(strict_types=1);

namespace Modules\Journal\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Journal\Models\JournalEntry;
use Modules\Journal\Policies\JournalPolicy;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class JournalServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Journal';

    protected string $nameLower = 'journal';

    /**
     * The policy mappings for the module.
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        JournalEntry::class => JournalPolicy::class,
    ];

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();
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
            \Modules\Journal\Services\Contracts\JournalService::class => \Modules\Journal\Services\JournalService::class,
        ];
    }

    protected function viewSlots(): array
    {
        return [
            'navbar.items' => 'journal::components.nav-link',
            'student.dashboard.quick-actions' => [
                'ui::components.button' => [
                    'label' => __('Jurnal Harian'),
                    'icon' => 'tabler.book',
                    'link' => '/journal',
                    'class' => 'btn-ghost justify-start w-full',
                ],
            ],
        ];
    }
}
