<?php

declare(strict_types=1);

namespace Modules\Auth\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as BaseAuthServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Modules\Auth\Livewire\Login;
use Modules\Auth\Registration\Livewire\Register;
use Modules\Auth\Registration\Livewire\RegisterSuperAdmin;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\RedirectService;
use Modules\Auth\Verification\Livewire\VerificationNotice;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class AuthServiceProvider extends BaseAuthServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Auth';

    protected string $nameLower = 'auth';

    /**
     * Boot the authentication / authorization services.
     */
    public function boot(): void
    {
        $this->bootModule();

        $this->registerLivewireComponents();

        // Customize the verification email to sound like it's from the school
        $this->customizeVerificationEmail();

        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the module.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });
    }

    /**
     * Register Livewire components for the module.
     */
    protected function registerLivewireComponents(): void
    {
        Livewire::component('auth::login', Login::class);
        Livewire::component('auth::register', Register::class);
        Livewire::component('auth::register-super-admin', RegisterSuperAdmin::class);
        Livewire::component(
            'auth::verify-email',
            \Modules\Auth\Verification\Livewire\VerifyEmail::class,
        );
        Livewire::component('auth::verification-notice', VerificationNotice::class);
    }

    /**
     * Configure the customized email verification message.
     */
    protected function customizeVerificationEmail(): void
    {
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return new MailMessage()
                ->subject(__('auth::emails.verification_subject'))
                ->greeting(__('auth::emails.verification_greeting', ['name' => $notifiable->name]))
                ->line(__('auth::emails.verification_line_1'))
                ->line(__('auth::emails.verification_line_2'))
                ->action(__('auth::emails.verification_action'), $url)
                ->line(__('auth::emails.verification_line_3'))
                ->salutation(
                    __('auth::emails.verification_salutation', [
                        'school' => setting('brand_name', 'Sekolah/Instansi'),
                    ]),
                );
        });
    }

    /**
     * Register any authentication / authorization services.
     */
    public function register(): void
    {
        $this->registerModule();

        // Register other service providers from this (Auth) module
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
            \Modules\Auth\Services\Contracts\AuthService::class => AuthService::class,
            \Modules\Auth\Services\Contracts\RedirectService::class => RedirectService::class,
        ];
    }

    protected function viewSlots(): array
    {
        return [
            'register.super-admin' => 'livewire:auth::register-super-admin',
        ];
    }
}
