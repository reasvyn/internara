<?php

declare(strict_types=1);

namespace Modules\Setup\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\SystemInstaller;

/**
 * Class AppInstallCommand
 *
 * Automates the technical installation and initialization of the Internara application.
 */
class AppInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:install {--force : Force the installation even if already installed}';

    /**
     * The console command description.
     */
    protected $description = 'Automated technical system initialization and installation';

    /**
     * Create a new command instance.
     */
    public function __construct(
        protected SystemInstaller $installerService,
        protected SettingService $settingService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayBanner();
        $this->displayPreFlightSummary();

        if (! $this->confirmInstallation()) {
            $this->components->warn(__('setup::install.warnings.aborted'));

            return self::FAILURE;
        }

        try {
            // 0. System Cleanup
            $this->performTask(
                __('setup::install.tasks.cleanup'),
                fn () => $this->callSilent('optimize:clear') === 0,
            );

            // 1. Environment Initialization
            $this->performTask(
                __('setup::install.tasks.env'),
                fn () => $this->installerService->ensureEnvFileExists(),
            );

            // 2. Environment Validation
            $this->performTask(__('setup::install.tasks.validation'), function () {
                $audit = $this->installerService->validateEnvironment();
                $failures = [];

                foreach (['requirements', 'permissions'] as $category) {
                    foreach ($audit[$category] ?? [] as $name => $status) {
                        if ($status === false) {
                            $failures[] = "{$category}.{$name}";
                        }
                    }
                }

                if (isset($audit['database']) && ! ($audit['database']['connection'] ?? false)) {
                    $failures[] =
                        'database.connection: '.
                        ($audit['database']['message'] ?? 'Unknown error');
                }

                if (count($failures) > 0) {
                    $this->newLine();
                    foreach ($failures as $failure) {
                        $this->components->error("  • {$failure}");
                    }

                    return false;
                }

                return true;
            });

            // 3. Application Key Generation
            $this->performTask(
                __('setup::install.tasks.key'),
                fn () => $this->installerService->generateAppKey(),
            );

            // 4. Database Schema Initialization
            $this->performTask(
                __('setup::install.tasks.schema'),
                fn () => $this->installerService->runMigrations($this->option('force')),
            );

            // 5. Foundational Data Seeding
            $this->performTask(
                __('setup::install.tasks.seeding'),
                fn () => $this->installerService->runSeeders(),
            );

            // 6. Storage System Integration
            $this->performTask(
                __('setup::install.tasks.storage'),
                fn () => $this->installerService->createStorageSymlink(),
            );
        } catch (\RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->components->error('System Initialization Failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->displayDeploymentSummary();

        return self::SUCCESS;
    }

    protected function displayBanner(): void
    {
        $this->newLine();
        $this->line(
            ' <fg=white;bg=blue;options=bold> INTERNARA </> <fg=blue;options=bold>'.
                __('setup::install.banner.engine').
                '</>',
        );
        $this->line(
            ' <fg=gray>'.
                __('setup::install.banner.tool', ['version' => config('app.version', '0.15.0')]).
                '</>',
        );
        $this->newLine();
    }

    protected function displayPreFlightSummary(): void
    {
        $this->components->twoColumnDetail(__('setup::install.preflight.php'), PHP_VERSION);
        $this->components->twoColumnDetail(__('setup::install.preflight.env'), config('app.env'));
        $this->components->twoColumnDetail(
            __('setup::install.preflight.db'),
            config('database.default'),
        );
        $this->newLine();
    }

    protected function performTask(string $title, \Closure $task): void
    {
        $result = $this->components->task($title, $task);

        if ($result === false) {
            throw new \RuntimeException("Critical system task failure: {$title}");
        }
    }

    protected function displayDeploymentSummary(): void
    {
        $this->newLine();
        $this->components->info(__('setup::install.success'));

        $token = $this->settingService->getValue('setup_token');

        if (! $this->settingService->getValue('setup_token_expires_at')) {
            $this->settingService->setValue(
                'setup_token_expires_at',
                now()->addHours(24)->toIso8601String(),
            );
        }

        $setupUrl = URL::temporarySignedRoute('setup', now()->addHours(24), ['token' => $token]);

        $this->line(' <fg=blue;options=bold>'.__('setup::install.auth_required').'</>');
        $this->newLine();
        $this->line("  <fg=cyan;options=bold>{$setupUrl}</>");
        $this->newLine();
    }

    protected function confirmInstallation(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        if (config('app.env') === 'production') {
            return $this->confirm(__('setup::install.warnings.production_confirm'), false);
        }

        return $this->confirm(__('setup::install.confirmation'), false);
    }
}
