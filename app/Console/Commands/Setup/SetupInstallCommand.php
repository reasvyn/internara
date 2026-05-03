<?php

declare(strict_types=1);

namespace App\Console\Commands\Setup;

use App\Domain\Core\Support\AppInfo;
use App\Domain\Setup\Actions\InstallSystemAction;
use App\Exceptions\SetupException;
use App\Services\Setup\EnvAuditor;
use App\Services\Setup\SetupService;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

/**
 * CLI system installation with signed URL output for web wizard continuation.
 *
 * S1 - Secure: Atomic installation, token-based URL generation, environment audit.
 * S2 - Sustain: Modern interactive UI via Laravel Prompts, comprehensive checks.
 * S3 - Scalable: Stateless token generation for web continuation.
 */
class SetupInstallCommand extends Command
{
    protected $signature = 'setup:install {--force : Force installation even if already installed}';

    protected $description = 'Install system technically and generate a URL with token for web setup wizard';

    public function handle(
        InstallSystemAction $installSystem,
        EnvAuditor $auditor,
        SetupService $setupService,
    ): int {
        intro(__('setup.cli.banner_title').' ('.AppInfo::version().')');

        $this->displayPreFlightSummary();

        $isInstalled = $setupService->isInstalled();

        // Check if already installed
        if ($isInstalled && ! $this->option('force')) {
            error(__('setup.cli.already_installed'));

            return self::FAILURE;
        }

        if ($isInstalled && $this->option('force')) {
            warning(__('setup.cli.forcing_reinstall'));
            $setupService->reset();
        } elseif (! $isInstalled) {
            $setupService->clearSession();
        }

        $shouldForceFresh = (bool) $this->option('force') || ! $isInstalled;

        // 1. Pre-flight Audit
        note(__('setup.cli.running_audit'));
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
            error(__('setup.cli.audit_failed'));

            return self::FAILURE;
        }

        $this->newLine();
        if (! confirm(__('setup.cli.proceed_confirm'), true)) {
            warning(__('setup.cli.aborted'));

            return self::SUCCESS;
        }

        $this->newLine();
        info(__('setup.cli.starting_installation'));

        try {
            $force = $shouldForceFresh;

            // 1. .env file
            spin(fn () => $installSystem->ensureEnvFileExists(), __('setup.cli.tasks.ensure_env'));

            // 2. App Key
            spin(fn () => $installSystem->ensureAppKeyExists(), __('setup.cli.tasks.generate_key'));

            // 3. Migrations
            spin(
                fn () => $installSystem->runMigrations($force),
                __('setup.cli.tasks.run_migrations'),
            );

            // 4. Seeders
            spin(fn () => $installSystem->runSeeders(), __('setup.cli.tasks.run_seeders'));

            // 5. Initial Settings
            spin(
                fn () => $installSystem->configureInitialSettings(),
                __('setup.cli.tasks.system_settings'),
            );

            // 6. Storage Link
            spin(fn () => $installSystem->linkStorage(), __('setup.cli.tasks.storage_link'));

            // 7. Final Optimization
            spin(fn () => $installSystem->optimize(), __('setup.cli.tasks.optimize'));

            // 8. Generate setup token and signed URL
            $token = $setupService->generateCliToken();
            $signedUrl = $this->generateSignedUrl($token);

            // 9. Output results
            $this->displaySuccess($token, $signedUrl);

            return self::SUCCESS;
        } catch (SetupException $e) {
            error($e->toCliOutput());

            $setupService->clearCliToken();

            return self::FAILURE;
        } catch (\Throwable $e) {
            error(__('setup.cli.installation_failed', ['message' => $e->getMessage()]));

            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            $setupService->clearCliToken();

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
        $this->components->twoColumnDetail(
            __('setup.cli.db_driver'),
            (string) config('database.default'),
        );
        $this->newLine();
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
        outro(__('setup.cli.installation_completed'));

        note(__('setup.cli.next_steps'));
        $this->line(' 1. '.__('setup.cli.visit_url'));
        $this->line('    <fg=cyan>'.$signedUrl.'</>');
        $this->newLine();
        $this->line(' 2. '.__('setup.cli.complete_wizard'));
        $this->newLine();
        $this->line(' <fg=gray>Token: '.$token.'</>');
        $this->line(' <fg=gray>'.__('setup.cli.token_expires').'</>');
        $this->newLine();
        warning(__('setup.cli.token_note'));
    }
}
