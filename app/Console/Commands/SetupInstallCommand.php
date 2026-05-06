<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Setup\InstallSystemAction;
use App\Actions\Setup\ProvisionSystemAction;
use App\Domain\Setup\Data\AuditReport;
use App\Domain\Setup\Enums\AuditCategory;
use App\Domain\Setup\Exceptions\SetupException;
use App\Domain\Setup\Services\EnvironmentAuditor;
use App\Domain\Shared\Enums\AuditStatus;
use App\Support\AppInfo;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

class SetupInstallCommand extends Command
{
    use \App\Console\Commands\Setup\Traits\InteractsWithInstallerCli;

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
            $report = $this->auditor->audit();
            $this->displayAuditResults($report);

            if (! $report->passed()) {
                error(__('setup.cli.audit_failed'));

                return self::FAILURE;
            }

            // Step 2: Confirmation (unless --force)
            if (! $this->option('force') && ! $this->confirmProceed()) {
                error(__('setup.cli.aborted'));

                return self::FAILURE;
            }

            // Step 3: Execute installation (audits again internally, then provisions + generates token)
            $this->newLine();
            $this->components->twoColumnDetail('  <fg=white;options=bold>'.__('setup.cli.starting_installation').'</>');

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
            $tokenData = \App\Models\Setup::generateToken();

            // Step 4: Display success
            $this->displaySuccess($tokenData['plaintext'], $tokenData['expires_at']);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->handleError($e);

            return self::FAILURE;
        }
    }

    protected function displayAuditResults(AuditReport $report): void
    {
        $categories = [
            AuditCategory::Requirements,
            AuditCategory::Permissions,
            AuditCategory::Database,
            AuditCategory::Terminal,
            AuditCategory::Recommendations,
        ];

        foreach ($categories as $category) {
            $checks = $report->byCategory($category);

            if ($checks === []) {
                continue;
            }

            $this->newLine();
            $this->components->twoColumnDetail('  <fg=green;options=bold>'.$category->label().'</>');

            foreach ($checks as $check) {
                $this->components->twoColumnDetail(
                    $check->name(),
                    $this->formatStatusWithMessage($check->status, $check->message()),
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
        $this->line("  ".__('setup.cli.token_expires').": <fg=yellow>{$expiresAt->format('H:i:s')}</> (in {$expiresAt->diffForHumans()})");
    }

    protected function handleError(\Throwable $e): void
    {
        $this->newLine();

        if ($e instanceof SetupException) {
            error($e->toCliOutput());
        } else {
            error(__('setup.cli.installation_failed', ['message' => $e->getMessage()]));
        }

        if ($this->option('verbose')) {
            $this->line('<fg=gray>'.$e->getTraceAsString().'</>');
        }
    }
}
