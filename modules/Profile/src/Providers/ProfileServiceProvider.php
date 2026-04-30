<?php

declare(strict_types=1);

namespace Modules\Profile\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Profile\Services\Contracts\ProfileService as ProfileServiceContract;
use Modules\Profile\Services\ProfileService;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;

class ProfileServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;

    protected string $name = 'Profile';

    protected string $nameLower = 'profile';

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
            ProfileServiceContract::class => ProfileService::class,
        ];
    }
}
