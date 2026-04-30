<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Setting\SetSettingAction;
use App\Support\AppInfo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * CLI system installation with signed URL output for web wizard continuation.
 *
 * S1 - Secure: Atomic installation, token-based URL generation.
 * S2 - Sustain: Clear output, minimal user interaction.
 * S3 - Scalable: Stateless token generation for web continuation.
 */
class SetupInstallCommand extends Command
{
    protected $signature = 'setup:install {--force : Force installation even if already installed}';
    protected $description = 'Install system technically and generate a URL with token for web setup wizard';

    public function handle(
        SetSettingAction $setSetting,
        LogAuditAction $logAudit,
    ): int {
        $this->displayBanner();

        // Check if already installed
        $lockFile = storage_path('app/.installed');
        if (File::exists($lockFile) && ! $this->option('force')) {
            $this->error(__('setup.cli.already_installed'));
            return self::FAILURE;
        }

        if (File::exists($lockFile) && $this->option('force')) {
            $this->warn(__('setup.cli.forcing_reinstall'));
            File::delete($lockFile);
        }

        $this->info(__('setup.cli.starting_installation'));
        $this->newLine();

        try {
            DB::transaction(function () use ($setSetting, $logAudit) {
            // 1. Run Migrations
            $this->task(__('setup.cli.tasks.migrations'), function () {
                Artisan::call('migrate:fresh', ['--force' => true]);
                return true;
            });

            // 2. Run Seeders
            $this->task(__('setup.cli.tasks.seeders'), function () {
                Artisan::call('db:seed', ['--force' => true]);
                return true;
            });

            // 3. Set system metadata (but NOT installed flag)
            $this->task(__('setup.cli.tasks.system_metadata'), function () use ($setSetting) {
                $setSetting->execute('app_version', AppInfo::version(), 'string', 'system');
                return true;
            });

            $this->task(__('setup.cli.tasks.installed_timestamp'), function () use ($setSetting) {
                $setSetting->execute('installed_at', now()->toIso8601String(), 'datetime', 'system');
                return true;
            });

            // 4. Log audit event
            $this->task(__('setup.cli.tasks.logging'), function () use ($logAudit) {
                $logAudit->execute(
                    action: 'system_installed_cli',
                    payload: ['version' => AppInfo::version()],
                    module: 'System'
                );
                return true;
            });
            });

        // 5. Link Storage (outside transaction)
        $this->task(__('setup.cli.tasks.storage_link'), function () {
            Artisan::call('storage:link', ['--force' => true]);
            return true;
        });

        // 6. Clear Cache (optimize:clear only as requested)
        $this->task(__('setup.cli.tasks.clear_cache'), function () {
            Artisan::call('optimize:clear');
            return true;
        });

            // 7. Generate setup token and signed URL
            $this->newLine();
            $token = $this->generateSetupToken();
            $signedUrl = $this->generateSignedUrl($token);

            // 8. Output results
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
     * Task helper for consistent output formatting.
     */
    protected function task(string $description, callable $task): void
    {
        $this->components->task($description, $task);
    }

    /**
     * Generate a setup token and store it.
     */
    protected function generateSetupToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = now()->addHours(24)->toIso8601String();

        $tokenData = [
            'token' => $token,
            'expires_at' => $expiresAt,
            'created_at' => now()->toIso8601String(),
        ];

        File::put(
            storage_path('app/setup_token.json'),
            json_encode($tokenData, JSON_PRETTY_PRINT)
        );

        // Also store in a location accessible by the web middleware
        File::put(
            storage_path('app/.setup_token'),
            $token . '|' . $expiresAt
        );

        return $token;
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

        $this->line(' <fg=white;bg=green;options=bold> ' . __('setup.cli.next_steps') . ' </>');
        $this->newLine();

        $this->line(' 1. ' . __('setup.cli.visit_url'));
        $this->line('    <fg=cyan>' . $signedUrl . '</>');
        $this->newLine();

        $this->line(' 2. ' . __('setup.cli.complete_wizard'));
        $this->newLine();

        $this->line(' <fg=gray>Token: ' . $token . '</>');
        $this->line(' <fg=gray>' . __('setup.cli.token_expires') . '</>');
        $this->newLine();

        $this->warn(__('setup.cli.token_note'));
    }

    /**
     * Display command banner.
     */
    protected function displayBanner(): void
    {
        $this->newLine();
        $this->line(' <fg=white;bg=blue;options=bold> SYSTEM INSTALLER </> <fg=blue;options=bold>CLI TOOL</>');
        $this->line(' <fg=gray>Version: ' . AppInfo::version() . '</>');
        $this->newLine();
    }
}
