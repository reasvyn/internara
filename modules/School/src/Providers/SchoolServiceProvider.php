<?php

declare(strict_types=1);

namespace Modules\School\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\School\Models\School;
use Modules\School\Policies\SchoolPolicy;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class SchoolServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'School';

    protected string $nameLower = 'school';

    /**
     * The policy mappings for the module.
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        School::class => SchoolPolicy::class,
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
            \Modules\School\Services\Contracts\SchoolService::class =>
                \Modules\School\Services\SchoolService::class,
        ];
    }

    protected function viewSlots(): array
    {
        return [
            'school-manager' => 'livewire:school::school-manager',
        ];
    }
}
