<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Setup\InstallSystemAction;
use App\Services\Setup\EnvAuditor;
use App\Services\Setup\SetupService;
use App\Support\AppInfo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * CLI system installation with signed URL output for web wizard continuation.
 *
 * S1 - Secure: Atomic installation, token-based URL generation, environment audit.
 * S2 - Sustain: Clear output, comprehensive checks, minimal user interaction.
 * S3 - Scalable: Stateless token generation for web continuation.
 */
class SetupInstallCommand extends Command
{
    protected $signature = 'setup:install {--force : Force installation even if already installed}';

    protected $description = 'Install system technically and generate a URL with token for web setup wizard';

    public function handle(
        InstallSystemAction $installSystem,
        EnvAuditor $auditor,
        SetupService $setupService
    ): int {
        $this->displayBanner();
        $this->displayPreFlightSummary();

        $isInstalled = $setupService->isInstalled();

        // Check if already installed
        if ($isInstalled && ! $this->option('force')) {
            $this->error(__('setup.cli.already_installed'));

            return self::FAILURE;
        }

        if ($isInstalled && $this->option('force')) {
            $this->warn(__('setup.cli.forcing_reinstall'));
            // reset() handles lock file deletion and session clearing
            $setupService->reset();
        } elseif (! $isInstalled) {
            // Clean any stale setup session data for new installation
            $setupService->clearSession();
        }

        // Determine if we should force a fresh migration
        // Force fresh if --force is used OR if it's the first technical installation
        $shouldForceFresh = (bool) $this->option('force') || ! $isInstalled;

        // 1. Initial Cleanup
        $this->task(__('setup.cli.tasks.clear_cache'), function () {
            Artisan::call('optimize:clear');

            return true;
        });

        // 2. Pre-flight Audit
        $this->info(__('setup.cli.running_audit'));
        $audit = $auditor->audit();

        foreach ($audit['categories'] as $category) {
            $this->newLine();
            $this->line(" <fg=gray>●</> <options=bold>{$category['label']}</>");

            foreach ($category['checks'] as $check) {
                $statusStr = match ($check['status']) {
                    'pass' => '<fg=green>PASS</>',
                    'fail' => '<fg=red>FAIL</>',
                    'warn' => '<fg=yellow>WARN</>',
                };
                $this->line("   [{$statusStr}] {$check['name']}: {$check['message']}");
            }
        }

        if (! $audit['passed']) {
            $this->newLine();
            $this->error(__('setup.cli.audit_failed'));

            return self::FAILURE;
        }

        $this->newLine();
        if (! $this->confirm(__('setup.cli.proceed_confirm'), true)) {
            $this->warn(__('setup.cli.aborted'));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info(__('setup.cli.starting_installation'));

        try {
            $force = $shouldForceFresh;

            // 1. .env file
            $this->task(__('setup.cli.tasks.ensure_env'), function () use ($installSystem) {
                $installSystem->ensureEnvFileExists();

                return true;
            });

            // 2. App Key
            $this->task(__('setup.cli.tasks.generate_key'), function () use ($installSystem) {
                $installSystem->ensureAppKeyExists();

                return true;
            });

            // 3. Migrations
            $this->task(__('setup.cli.tasks.run_migrations'), function () use ($installSystem, $force) {
                $installSystem->runMigrations($force);

                return true;
            });

            // 4. Seeders
            $this->task(__('setup.cli.tasks.run_seeders'), function () use ($installSystem) {
                $installSystem->runSeeders();

                return true;
            });

            // 5. Initial Settings
            $this->task(__('setup.cli.tasks.system_settings'), function () use ($installSystem) {
                $installSystem->configureInitialSettings();

                return true;
            });

            // 6. Storage Link
            $this->task(__('setup.cli.tasks.storage_link'), function () use ($installSystem) {
                $installSystem->linkStorage();

                return true;
            });

            // 7. Final Optimization
            $this->task(__('setup.cli.tasks.optimize'), function () use ($installSystem) {
                $installSystem->optimize();

                return true;
            });

            // 4. Generate setup token and signed URL
            $this->newLine();
            $token = $setupService->generateCliToken();
            $signedUrl = $this->generateSignedUrl($token);

            // 5. Output results
            $this->displaySuccess($token, $signedUrl);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error(__('setup.cli.installation_failed', ['message' => $e->getMessage()]));
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    /**
     * Technical summary of the current environment.
     */
    protected function displayPreFlightSummary(): void
    {
        $this->components->twoColumnDetail(__('setup.cli.php_version'), PHP_VERSION);
        $this->components->twoColumnDetail(__('setup.cli.environment'), (string) config('app.env'));
        $this->components->twoColumnDetail(__('setup.cli.db_driver'), (string) config('database.default'));
        $this->newLine();
    }

    /**
     * Task helper for consistent output formatting.
     */
    protected function task(string $description, callable $task): void
    {
        $this->components->task($description, $task);
    }

    /**
     * Generate a URL with setup token for the setup wizard.
     */
    protected function generateSignedUrl(string $token): string
    {
        return route('setup', ['setup_token' => $token]);
    }

    /**
     * Display success message with URL.
     */
    protected function displaySuccess(string $token, string $signedUrl): void
    {
        $this->newLine();
        $this->components->info(__('setup.cli.installation_completed'));
        $this->newLine();

        $this->line(' <fg=white;bg=green;options=bold> '.__('setup.cli.next_steps').' </>');
        $this->newLine();

        $this->line(' 1. '.__('setup.cli.visit_url'));
        $this->line('    <fg=cyan>'.$signedUrl.'</>');
        $this->newLine();

        $this->line(' 2. '.__('setup.cli.complete_wizard'));
        $this->newLine();

        $this->line(' <fg=gray>Token: '.$token.'</>');
        $this->line(' <fg=gray>'.__('setup.cli.token_expires').'</>');
        $this->newLine();

        $this->warn(__('setup.cli.token_note'));
    }

    /**
     * Display command banner.
     */
    protected function displayBanner(): void
    {
        $this->newLine();
        $this->line(' <fg=white;bg=blue;options=bold> '.__('setup.cli.banner_title').' </> <fg=blue;options=bold>'.__('setup.cli.banner_subtitle').'</>');
        $this->line(' <fg=gray>'.__('setup.cli.version').': '.AppInfo::version().'</>');
        $this->newLine();
    }
}
