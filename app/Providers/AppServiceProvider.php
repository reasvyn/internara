<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Support\Integrity;
use App\Domain\Core\Support\MailConfiguration;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
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
        // S2 - Sustain: Configure Eloquent strictness for better DX
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // S3 - Scalable: Custom factory discovery for DDD structure
        Factory::guessFactoryNamesUsing(function (string $modelName) {
            $baseName = class_basename($modelName);

            return 'Database\\Factories\\'.$baseName.'Factory';
        });

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
