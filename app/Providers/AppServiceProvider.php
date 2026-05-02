<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use App\Support\Integrity;
use App\Support\MailConfiguration;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // S1 - Secure: Global bypass for Super Admin
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole(Role::SUPER_ADMIN->value) ? true : null;
        });

        // S1 - Secure: Restrict Pulse dashboard to Super Admin and Admin only
        Gate::define('viewPulse', function (User $user) {
            return $user->hasAnyRole([Role::SUPER_ADMIN->value, Role::ADMIN->value]);
        });

        // S2 - Sustain: Protect author credit for OSS Internara
        Integrity::verify();

        // S2 - Sustain: Apply dynamic mail configuration from settings
        MailConfiguration::apply();
    }
}
