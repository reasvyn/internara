<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Admin\Actions\SendNotificationAction;
use App\Domain\Auth\Policies\UserPolicy;
use App\Domain\Core\Contracts\SendsNotifications;
use App\Domain\Internship\Policies\CompanyPolicy;
use App\Domain\Internship\Policies\InternshipRegistrationPolicy;
use App\Domain\Partnership\Models\Company;
use App\Domain\Placement\Models\Placement;
use App\Domain\Placement\Policies\InternshipPlacementPolicy;
use App\Domain\Registration\Models\Registration;
use App\Domain\Setup\Events\SetupFinalized;
use App\Domain\Setup\Listeners\LogSetupFinalized;
use App\Domain\Shared\Support\LangChecker;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SendsNotifications::class, SendNotificationAction::class);

        if ($this->app->hasDebugModeEnabled()) {
            $this->app->extend('translator', fn ($translator) => tap(
                new LangChecker($translator->getLoader(), $translator->getLocale()),
                fn (LangChecker $checker) => $checker->setFallback($translator->getFallback()),
            ));
        }
    }

    public function boot(): void
    {
        Event::listen(
            SetupFinalized::class,
            [LogSetupFinalized::class, 'handle'],
        );

        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');
        Blade::anonymousComponentPath(resource_path('views/components/ui'), 'ui');
        Blade::anonymousComponentPath(resource_path('views/components/widget'), 'widget');

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Placement::class, InternshipPlacementPolicy::class);
        Gate::policy(Registration::class, InternshipRegistrationPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
    }
}
