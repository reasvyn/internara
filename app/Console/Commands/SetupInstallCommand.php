<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Setup\InstallSystemAction;
use App\Actions\Setup\ProvisionSystemAction;
use App\Console\Commands\Setup\Traits\InteractsWithInstallerCli;
use App\Enums\Setup\AuditCategory;
use App\Enums\Shared\AuditStatus;
use App\Models\Setup;
use App\Services\Setup\EnvironmentAuditor;
use App\Support\Logger;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;

class SetupInstallCommand extends Command
{
    use InteractsWithInstallerCli;

    protected $signature = 'setup:install {--force : Force installation even if already installed}';

    public function __construct(
        private EnvironmentAuditor $auditor,
        private InstallSystemAction $installSystem,
    ) {
        parent::__construct();
        $this->description = __('setup.cli.starting_installation');
    }

    public function handle(): int
    {
        $this->displayBanner();

        try {
            // Step 1: Run audit and display results
            $checks = $this->auditor->audit();
            $this->displayAuditResults($checks);

            $failed = array_values(array_filter(
                $checks,
                fn (array $check) => $check['category']->isCritical() && $check['status'] === AuditStatus::Fail,
            ));

            if ($failed !== []) {
                Logger::error(__('setup.cli.audit_failed'))
                    ->module('setup')
                    ->event('audit.failed')
                    ->save();

                error(__('setup.cli.audit_failed'));

                return self::FAILURE;
            }

            // Step 2: Confirmation (unless --force)
            if ($this->option('force')) {
                $this->components->warn(__('setup.cli.force_warning'));
            } elseif (! $this->confirmProceed()) {
                error(__('setup.cli.aborted'));

                return self::FAILURE;
            }

            // Step 3: Execute installation (audits again internally, then provisions + generates token)
            $this->newLine();
            $this->components->twoColumnDetail('  <fg=white;options=bold>'.__('setup.cli.starting_installation').'</>');

            Logger::info(__('setup.cli.starting_installation'))
                ->module('setup')
                ->event('installation.started')
                ->save();

            $provisioner = app(ProvisionSystemAction::class);
            $force = (bool) $this->option('force');

            foreach ($provisioner->getTasks() as $key => $label) {
                try {
                    $provisioner->executeTask($key, $force);
                    $this->components->twoColumnDetail($label, '<fg=green>DONE</>');
                } catch (\Throwable $e) {
                    $this->components->twoColumnDetail($label, '<fg=red>FAIL</>');
                    throw $e;
                }
            }

            // Generate and store setup token (moved from InstallSystemAction to maintain reporting consistency)
            $tokenData = Setup::generateToken();

            // Step 4: Display success
            $this->displaySuccess($tokenData['plaintext'], $tokenData['expires_at']);

            Logger::success(__('setup.cli.installation_completed'))
                ->module('setup')
                ->event('installation.completed')
                ->save();

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Logger::error(__('setup.cli.installation_failed', ['message' => $e->getMessage()]))
                ->module('setup')
                ->event('installation.failed')
                ->withPayload(['error' => $e->getMessage()])
                ->save();

            $this->handleError($e);

            return self::FAILURE;
        }
    }

    protected function displayAuditResults(array $checks): void
    {
        $categories = [
            AuditCategory::Requirements,
            AuditCategory::Permissions,
            AuditCategory::Database,
            AuditCategory::Terminal,
            AuditCategory::Recommendations,
        ];

        foreach ($categories as $category) {
            $categoryChecks = array_values(array_filter(
                $checks,
                fn (array $check) => $check['category'] === $category,
            ));

            if ($categoryChecks === []) {
                continue;
            }

            $this->newLine();
            $this->components->twoColumnDetail('  <fg=green;options=bold>'.$category->label().'</>');

            foreach ($categoryChecks as $check) {
                $this->components->twoColumnDetail(
                    __("setup.checks.{$check['nameKey']}", $check['nameParams']),
                    $this->formatStatusWithMessage($check['status'], __("setup.checks.{$check['messageKey']}", $check['messageParams'])),
                );
            }
        }

        $this->newLine();
    }

    private function formatStatusWithMessage(AuditStatus $status, string $message): string
    {
        $color = match ($status) {
            AuditStatus::Pass => 'green',
            AuditStatus::Fail => 'red',
            AuditStatus::Warn => 'yellow',
        };

        return "<fg={$color}>{$message}</>";
    }

    protected function confirmProceed(): bool
    {
        return confirm(
            label: __('setup.cli.proceed_confirm'),
            default: true,
        );
    }

    protected function displaySuccess(string $token, Carbon $expiresAt): void
    {
        $signedUrl = route('setup', ['setup_token' => $token]);

        $this->displayCompletion();

        $this->newLine();
        $this->line('  <fg=cyan;options=bold,underscore>'.$signedUrl.'</>');

        $this->newLine();
        $this->line("  Token: <fg=white;options=bold>{$token}</>");
        $this->line('  '.__('setup.cli.token_expires').": <fg=yellow>{$expiresAt->format('H:i:s')}</> (in {$expiresAt->diffForHumans()})");
    }

    protected function handleError(\Throwable $e): void
    {
        $this->newLine();

        error($e->getMessage());

        if ($this->option('verbose')) {
            $this->line('<fg=gray>'.$e->getTraceAsString().'</>');
        }
    }
}
