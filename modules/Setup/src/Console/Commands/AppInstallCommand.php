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
            $success = true;

            // 0. System Cleanup
            $this->components->task('Clearing application cache', function () {
                $this->callSilent('optimize:clear');

                return true;
            });

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
                    if (! isset($audit[$category]) || ! is_array($audit[$category])) {
                        continue;
                    }

                    foreach ($audit[$category] as $name => $status) {
                        if ($status === false) {
                            $this->newLine();
                            $label = is_string($name)
                                ? $name
                                : (is_numeric($name)
                                    ? (string) $name
                                    : json_encode($name));
                            $this->components->error("Audit failed for: {$label}");
                            $failedCount++;
                        }
                    }
                }

                // Check Database specifically
                if (isset($audit['database']) && ! ($audit['database']['connection'] ?? false)) {
                    $this->newLine();
                    $this->components->error(
                        'Database error: '.
                            (is_array($audit['database']['message'])
                                ? json_encode($audit['database']['message'])
                                : (string) ($audit['database']['message'] ?? 'Unknown error')),
                    );
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
        } catch (\Throwable $e) {
            $this->newLine();
            $this->components->error('Installation Failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            
            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Technical installation completed successfully!');

        $token = $this->settingService->getValue('setup_token');
        $setupUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'setup.welcome',
            now()->addHours(24),
            ['token' => $token],
        );

        $this->info('Please proceed to the Web Setup Wizard using the authorized link below:');
        $this->warn($setupUrl);

        if (
            parse_url($setupUrl, PHP_URL_PORT) === null &&
            config('app.url') === 'http://localhost'
        ) {
            $this->newLine();
            $this->info(
                'Note: If you are using a specific port (e.g., via artisan serve), ensure the port is included in the URL.',
            );
        }

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
