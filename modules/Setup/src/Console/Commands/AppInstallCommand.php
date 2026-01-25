<?php

declare(strict_types=1);

namespace Modules\Setup\Console\Commands;

use Illuminate\Console\Command;
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
        protected InstallerService $installerService
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

        // 1. Environment Validation
        $this->components->task('Validating environment requirements', function () {
            $requirements = $this->installerService->validateEnvironment();

            foreach ($requirements as $name => $status) {
                if (! $status) {
                    $this->newLine();
                    $this->components->error("Requirement failed: {$name}");

                    return false;
                }
            }

            return true;
        });

        // 2. Database Migrations
        $this->components->task('Running database migrations', function () {
            return $this->installerService->runMigrations();
        });

        // 3. Core & Shared Seeding
        $this->components->task('Seeding foundational data', function () {
            return $this->installerService->runSeeders();
        });

        // 4. Storage Symlinking
        $this->components->task('Creating storage symbolic link', function () {
            return $this->installerService->createStorageSymlink();
        });

        $this->newLine();
        $this->components->info('Technical installation completed successfully!');
        $this->info('You can now proceed to the Web Setup Wizard to configure institutional branding.');
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

        return $this->confirm('This will reset your database and initialize the system. Do you want to proceed?', false);
    }
}
