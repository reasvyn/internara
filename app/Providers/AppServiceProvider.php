<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Auth\Domain\Permission\Policies\UserPolicy;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Certification\Domain\Certificate\Observers\CertificateObserver;
use App\Modules\Core\Contracts\SendsNotifications;
use App\Modules\Core\Contracts\SettingsStore;
use App\Modules\Core\Services\LangChecker;
use App\Modules\Core\Services\ModuleService;
use App\Modules\Core\Support\ModuleManager;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Placement\Observers\PlacementObserver;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Enrollment\Domain\Registration\Observers\RegistrationObserver;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use App\Modules\Journal\Domain\Attendance\Observers\AttendanceObserver;
use App\Modules\Journal\Domain\Logbook\Models\Logbook;
use App\Modules\Journal\Domain\Logbook\Observers\LogbookObserver;
use App\Modules\Report\Domain\StudentReport\Models\StudentReport;
use App\Modules\Report\Domain\StudentReport\Observers\StudentReportObserver;
use App\Modules\Setting\Services\Settings;
use App\Modules\User\Domain\Notify\Actions\SendNotificationAction;
use App\Modules\User\Models\User;
use App\Modules\User\Observers\UserObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use TallStackUi\Facades\TallStackUi;

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
            fn (Request $request) => Limit::perMinute(30)->by($request->ip()),
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

        Registration::observe(RegistrationObserver::class);
        Logbook::observe(LogbookObserver::class);
        Attendance::observe(AttendanceObserver::class);
        Placement::observe(PlacementObserver::class);
        Certificate::observe(CertificateObserver::class);
        User::observe(UserObserver::class);
        StudentReport::observe(StudentReportObserver::class);

        try {
            if ($mailHost = Settings::get('mail_host')) {
                config([
                    'mail.mailers.smtp.host' => $mailHost,
                    'mail.mailers.smtp.port' => (int) Settings::get('mail_port', 587),
                    'mail.mailers.smtp.encryption' => Settings::get('mail_encryption', 'tls'),
                    'mail.mailers.smtp.username' => Settings::get('mail_username'),
                    'mail.mailers.smtp.password' => Settings::get('mail_password'),
                    'mail.from.address' => Settings::get('mail_from_address', config('mail.from.address')),
                    'mail.from.name' => Settings::get('mail_from_name', config('mail.from.name')),
                ]);
            }
        } catch (\Throwable) {
            // Ignore during early bootstrap or tests without setting tables
        }

        // Sidebar: labels neutral-800, icons primary (semantic from setting).
        TallStackUi::customize()
            ->sideBar('separator')
            ->block('simple.base')
            ->replace('text-primary-600', 'text-neutral-800')
            ->replace('dark:text-dark-100', 'dark:text-white')
            ->and()
            ->sideBar('item')
            ->block('item.state.normal')
            ->replace('text-primary-500', 'text-neutral-800')
            ->and()
            ->sideBar('item')
            ->block('item.state.current')
            ->replace('text-primary-500', 'text-neutral-800')
            ->replace('bg-primary-50', 'bg-primary/10')
            ->and()
            ->sideBar('item')
            ->block('group.button')
            ->replace('text-primary-500', 'text-neutral-800')
            ->and()
            // Icons keep primary color from theme setting
            ->sideBar('item')
            ->block('group.icon.base')
            ->replace('text-primary-500', 'text-primary')
            ->and()
            ->sideBar('item')
            ->block('item.icon')
            ->replace('text-primary-500', 'text-primary')
            ->and()
            ->sideBar('separator')
            ->block('line.base')
            ->replace('text-primary-600', 'text-neutral-800')
            ->replace('dark:text-dark-100', 'dark:text-white')
            ->and()
            ->sideBar('separator')
            ->block('line-right.base')
            ->replace('text-primary-600', 'text-neutral-800')
            ->replace('dark:text-dark-100', 'dark:text-white')
            ->and()
            // Layout: responsive main padding (p-4 mobile, p-6 tablet, p-8 desktop) instead of hardcoded p-10
            ->layout()
            ->block('main')
            ->replace('p-10', 'p-4 md:p-6 lg:p-8');
    }

    protected function service(): ModuleService
    {
        return $this->app->make(ModuleService::class);
    }
}
