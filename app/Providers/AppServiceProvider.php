<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\Permissions\Policies\UserPolicy;
use App\Core\Contracts\SendsNotifications;
use App\Core\Contracts\SettingsStore;
use App\Core\Services\LangChecker;
use App\Core\Services\ModuleService;
use App\Core\Support\ModuleManager;
use App\Settings\Services\Settings;
use App\User\Models\User;
use App\User\Notifications\Actions\SendNotificationAction;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->hasDebugModeEnabled()) {
            $this->app->extend(
                'translator',
                fn ($translator) => tap(
                    new LangChecker($translator->getLoader(), $translator->getLocale()),
                    fn (LangChecker $checker) => $checker->setFallback($translator->getFallback()),
                ),
            );
        }

        $this->app->bind(SendsNotifications::class, SendNotificationAction::class);

        $this->app->singleton(SettingsStore::class, function () {
            return new class implements SettingsStore
            {
                public function get(string $key, mixed $default = null): mixed
                {
                    return Settings::get($key, $default);
                }
            };
        });
    }

    public function boot(): void
    {
        RateLimiter::for('admin', fn () => Limit::perMinute(60));
        RateLimiter::for(
            'global',
            fn (Request $request) => Limit::perMinute(120)->by($request->ip()),
        );

        if (ModuleManager::policiesEnabled()) {
            $this->service()->discoverPolicies();
        }

        Gate::policy(User::class, UserPolicy::class);

        if (ModuleManager::livewireEnabled()) {
            $this->service()->discoverLivewireComponents();
        }

        if (ModuleManager::viewsEnabled()) {
            $this->service()->registerBladeNamespaces();
        }
    }

    protected function service(): ModuleService
    {
        return $this->app->make(ModuleService::class);
    }
}
