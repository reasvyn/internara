<?php

declare(strict_types=1);

namespace App\Domain\Setup\Console\Commands;

use App\Domain\Core\Data\AuditReport;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\Setup\Actions\GenerateSetupTokenAction;
use App\Domain\Setup\Console\Commands\Traits\InteractsWithInstallerCli;
use App\Domain\Setup\Services\EnvironmentAuditor;
use App\Domain\Setup\Support\SystemProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;

class SetupInstallCommand extends Command
{
    use InteractsWithInstallerCli;

    protected $signature = 'setup:install
        {--force : Force installation even if already installed}
        {--check-only : Run environment audit without provisioning}';

    public function __construct(
        private EnvironmentAuditor $auditor,
        private GenerateSetupTokenAction $generateToken,
    ) {
        parent::__construct();
        $this->description = __('setup.cli.starting_installation');
    }

    public function handle(): int
    {
        $this->displayBanner();

        if ($this->option('force')) {
            $this->components->warn(__('setup.cli.force_warning'));
        }

        try {
            if ($this->isInstalled() && ! $this->option('force')) {
                error(__('setup.cli.already_installed'));
                $this->info(__('setup.cli.try_health_check'));

                return self::FAILURE;
            }

            if ($this->option('force')) {
                $allowed = config('setup.force_allowed_environments', ['local', 'dev', 'development', 'testing']);
                if (! in_array(app()->environment(), $allowed, true)) {
                    error(__('setup.cli.force_restricted'));

                    return self::FAILURE;
                }
            }

            $report = $this->auditor->audit();
            $this->displayAuditResults($report);

            $failed = array_values(array_filter(
                $report->checks,
                fn ($check) => $this->isCriticalCategory($check->category) && $check->status === AuditStatus::FAIL,
            ));

            if ($failed !== []) {
                SmartLogger::error(__('setup.cli.audit_failed'))
                    ->module('setup')
                    ->event('audit.failed')
                    ->save();

                error(__('setup.cli.audit_failed'));

                return self::FAILURE;
            }

            if ($this->option('check-only')) {
                $this->info(__('setup.cli.check_only_complete'));

                return self::SUCCESS;
            }

            if (! $this->option('force') && ! $this->confirmProceed()) {
                error(__('setup.cli.aborted'));

                return self::FAILURE;
            }

            $this->newLine();
            $this->components->twoColumnDetail('  <fg=white;options=bold>'.__('setup.cli.starting_installation').'</>');

            SmartLogger::info(__('setup.cli.starting_installation'))
                ->module('setup')
                ->event('installation.started')
                ->save();

            $provisioner = app(SystemProvisioner::class);
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

            $tokenData = $this->generateToken->execute();

            $this->displaySuccess($tokenData['plaintext'], $tokenData['expires_at']);

            SmartLogger::success(__('setup.cli.installation_completed'))
                ->module('setup')
                ->event('installation.completed')
                ->save();

            return self::SUCCESS;
        } catch (\Throwable $e) {
            SmartLogger::error(__('setup.cli.installation_failed', ['message' => $e->getMessage()]))
                ->module('setup')
                ->event('installation.failed')
                ->withPayload(['error' => $e->getMessage()])
                ->save();

            $this->handleError($e);

            return self::FAILURE;
        }
    }

    protected function displayAuditResults(AuditReport $report): void
    {
        $categories = config('setup.audit_categories', [
            AuditCategory::REQUIREMENTS,
            AuditCategory::PERMISSIONS,
            AuditCategory::DATABASE,
            AuditCategory::TERMINAL,
            AuditCategory::RECOMMENDATIONS,
        ]);

        foreach ($categories as $category) {
            $categoryChecks = $report->forCategory($category);

            if ($categoryChecks === []) {
                continue;
            }

            $this->newLine();
            $this->components->twoColumnDetail('  <fg=green;options=bold>'.$category->label().'</>');

            foreach ($categoryChecks as $check) {
                $this->components->twoColumnDetail(
                    __("setup.checks.{$check->nameKey}", $check->nameParams),
                    $this->formatStatusWithMessage($check->status, __("setup.checks.{$check->messageKey}", $check->messageParams)),
                );
            }
        }

        $this->newLine();
    }

    private function formatStatusWithMessage(AuditStatus $status, string $message): string
    {
        $color = match ($status) {
            AuditStatus::PASS => 'green',
            AuditStatus::FAIL => 'red',
            AuditStatus::WARN => 'yellow',
        };

        return "<fg={$color}>{$message}</>";
    }

    private function isCriticalCategory(AuditCategory $category): bool
    {
        return $category->isCritical();
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
        $this->line('  '.__('setup.cli.token_expires').": <fg=yellow>{$expiresAt->format('H:i:s T')}</> (in {$expiresAt->diffForHumans()})");
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
