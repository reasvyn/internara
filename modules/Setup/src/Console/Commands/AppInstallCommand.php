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

        $success = true;

        // 1. Environment Initialization
        $this->components->task('Ensuring .env file existence', function () use (&$success) {
            if (! $this->installerService->ensureEnvFileExists()) {
                $success = false;

                return false;
            }

            return true;
        });

        if (! $success) {
            return self::FAILURE;
        }

        // 2. Environment Validation
        $this->components->task('Validating environment requirements', function () use (&$success) {
            $audit = $this->installerService->validateEnvironment();
            $failedCount = 0;

            // Check Requirements & Permissions
            foreach (['requirements', 'permissions'] as $category) {
                foreach ($audit[$category] as $name => $status) {
                    if ($status === false) {
                        $this->newLine();
                        $this->components->error("Audit failed: {$name}");
                        $failedCount++;
                    }
                }
            }

            // Check Database specifically
            if (! $audit['database']['connection']) {
                $this->newLine();
                $this->components->error("Database error: " . $audit['database']['message']);
                $failedCount++;
            }

            if ($failedCount > 0) {
                $success = false;
                return false;
            }

            return true;
        });

        if (! $success) {
            return self::FAILURE;
        }

        // 3. Application Key Generation
        $this->components->task('Generating application key', function () use (&$success) {
            if (! $this->installerService->generateAppKey()) {
                $success = false;

                return false;
            }

            return true;
        });

        if (! $success) {
            return self::FAILURE;
        }

        // 4. Database Migrations
        $this->components->task('Initializing database schema', function () use (&$success) {
            if (! $this->installerService->runMigrations()) {
                $success = false;

                return false;
            }

            return true;
        });

        if (! $success) {
            return self::FAILURE;
        }

        // 5. Core & Shared Seeding
        $this->components->task('Seeding foundational data', function () use (&$success) {
            if (! $this->installerService->runSeeders()) {
                $success = false;

                return false;
            }

            return true;
        });

        if (! $success) {
            return self::FAILURE;
        }

        // 6. Storage Symlinking
        $this->components->task('Creating storage symbolic link', function () use (&$success) {
            if (! $this->installerService->createStorageSymlink()) {
                $success = false;

                return false;
            }

            return true;
        });

        if (! $success) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Technical installation completed successfully!');

        $token = $this->settingService->getValue('setup_token');
        $setupUrl = route('setup.welcome', ['token' => $token]);

        $this->info('Please proceed to the Web Setup Wizard using the authorized link below:');
        $this->warn($setupUrl);
        $this->newLine();

        return self::SUCCESS;
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
