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
        $this->components->info('Internara System Installation');

        if (! $this->confirmInstallation()) {
            return self::FAILURE;
        }

        try {
            // 0. System Cleanup
            $this->performTask('Clearing application cache', fn () => $this->callSilent('optimize:clear') === 0);

            // 1. Environment Initialization
            $this->performTask('Ensuring .env file existence', fn () => $this->installerService->ensureEnvFileExists());

            // 2. Environment Validation
            $this->performTask('Validating environment requirements', function () {
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
                    $failures[] = 'database.connection (' . ($audit['database']['message'] ?? 'Unknown error') . ')';
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
            $this->performTask('Generating application key', fn () => $this->installerService->generateAppKey());

            // 4. Database Migrations
            $this->performTask('Initializing database schema', fn () => $this->installerService->runMigrations());

            // 5. Core & Shared Seeding
            $this->performTask('Seeding foundational data', fn () => $this->installerService->runSeeders());

            // 6. Storage Symlinking
            $this->performTask('Creating storage symbolic link', fn () => $this->installerService->createStorageSymlink());

        } catch (\RuntimeException $e) {
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->components->error('Installation Failed: ' . $e->getMessage());
            
            if (config('app.debug')) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }

        $this->displaySuccessMessage();

        return self::SUCCESS;
    }

    /**
     * Helper to perform a task and abort on failure.
     */
    protected function performTask(string $title, \Closure $task): void
    {
        $result = $this->components->task($title, $task);

        if ($result === false) {
            throw new \RuntimeException("Task failed: {$title}");
        }
    }

    /**
     * Display the final success message and instructions.
     */
    protected function displaySuccessMessage(): void
    {
        $this->newLine();
        $this->components->info('Technical installation completed successfully!');

        $token = $this->settingService->getValue('setup_token');
        $setupUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'setup.welcome',
            now()->addHours(24),
            ['token' => $token],
        );

        $this->line(' <bg=blue;options=bold> NEXT STEP </> Please proceed to the Web Setup Wizard:');
        $this->line(" <fg=cyan;options=bold>{$setupUrl}</>");
        $this->newLine();

        if (parse_url($setupUrl, PHP_URL_PORT) === null && config('app.url') === 'http://localhost') {
            $this->components->warn('Note: Ensure the port is included if you are using a non-standard port.');
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
