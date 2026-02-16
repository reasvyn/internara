<?php

declare(strict_types=1);

namespace Modules\Setting\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class SettingServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Setting';

    protected string $nameLower = 'setting';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();

        // Include the global helper function
        require_once module_path($this->name, 'src/Functions/setting.php');

        $this->syncSettingsToConfig();
    }

    /**
     * Synchronize dynamic settings to Laravel configuration.
     */
    protected function syncSettingsToConfig(): void
    {
        // Only sync if the application is installed to avoid database errors during early setup
        if (! $this->app->runningInConsole() || $this->app->bound('db')) {
            try {
                // 1. Determine the sender identity: mail_from_name > brand_name > app_name
                $senderName =
                    setting('mail_from_name') ?:
                    setting('brand_name', setting('app_name', 'Internara'));

                $senderAddress = setting('mail_from_address', config('mail.from.address'));

                config([
                    'mail.from.name' => $senderName,
                    'mail.from.address' => $senderAddress,
                ]);

                // 2. Synchronize SMTP Server Configuration
                if (setting('mail_host')) {
                    config([
                        'mail.mailers.smtp.host' => setting('mail_host'),
                        'mail.mailers.smtp.port' => setting('mail_port', 587),
                        'mail.mailers.smtp.username' => setting('mail_username'),
                        'mail.mailers.smtp.password' => setting('mail_password'),
                        'mail.mailers.smtp.encryption' => setting('mail_encryption', 'tls'),
                    ]);
                }
            } catch (\Throwable $e) {
                // Fail silently during early boot or if table doesn't exist
            }
        }
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
            \Modules\Setting\Services\Contracts\SettingService::class => \Modules\Setting\Services\SettingService::class,
        ];
    }
}
