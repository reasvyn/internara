<?php

declare(strict_types=1);

namespace Modules\Setup\Console\Commands;

use Illuminate\Console\Command;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\InstallerService;

/**
 * Class AppInstallCommand
 *
 * Automates the technical installation and initialization of the Internara application.
 */
class AppInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install {--force : Force the installation even if already installed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated system initialization and technical installation';

    /**
     * Create a new command instance.
     */
    public function __construct(
        protected InstallerService $installerService,
        protected SettingService $settingService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->components->info('Internara System Initialization');

        if (! $this->confirmInstallation()) {
            return self::FAILURE;
        }

        try {
            // 0. System Cleanup
            $this->performTask('Clearing application cache', fn () => $this->callSilent('optimize:clear') === 0);

            // 1. Environment Initialization
            $this->performTask('Ensuring environment configuration', fn () => $this->installerService->ensureEnvFileExists());

            // 2. Environment Validation
            $this->performTask('Validating system requirements', function () {
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
                    $rawMessage = (string) ($audit['database']['message'] ?? 'Unknown connection error');
                    // Security: Sanitize sensitive data from potential raw DB errors
                    $sanitizedMessage = preg_replace('/(password|pwd|user|usr)=[^; ]+/i', '$1=****', $rawMessage);
                    $failures[] = "database.connection: {$sanitizedMessage}";
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
            $this->performTask('Generating application security key', fn () => $this->installerService->generateAppKey());

            // 4. Database Schema Initialization
            $this->performTask('Initializing database schema', fn () => $this->installerService->runMigrations());

            // 5. Foundational Data Seeding
            $this->performTask('Seeding foundational datasets', fn () => $this->installerService->runSeeders());

            // 6. Storage System Integration
            $this->performTask('Integrating storage system', fn () => $this->installerService->createStorageSymlink());

        } catch (\RuntimeException $e) {
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->components->error('System Initialization Failed: ' . $e->getMessage());
            
            if (config('app.debug')) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }

        $this->displayDeploymentSummary();

        return self::SUCCESS;
    }

    /**
     * Helper to perform a task and abort on failure.
     */
    protected function performTask(string $title, \Closure $task): void
    {
        $result = $this->components->task($title, $task);

        if ($result === false) {
            throw new \RuntimeException("Critical task failure: {$title}");
        }
    }

    /**
     * Display the final deployment summary and next steps.
     */
    protected function displayDeploymentSummary(): void
    {
        $this->newLine();
        $this->components->info('Core system initialization completed successfully.');

        $token = $this->settingService->getValue('setup_token');
        $setupUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'setup.welcome',
            now()->addHours(24),
            ['token' => $token],
        );

        $this->line(' <fg=blue;options=bold>Authorization Required</>');
        $this->line(' Please use the following authenticated link to finalize the system configuration:');
        $this->newLine();
        $this->line("  <fg=cyan>{$setupUrl}</>");
        $this->newLine();

        if (parse_url($setupUrl, PHP_URL_PORT) === null && config('app.url') === 'http://localhost') {
            $this->components->warn('Environment Notice: Port mapping may be required for external access.');
        }

        $this->newLine();
    }

    /**
     * Confirm if the installation should proceed.
     */
    protected function confirmInstallation(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        return $this->confirm(
            'This will reset your database and initialize the system. Do you want to proceed?',
            false,
        );
    }
}
