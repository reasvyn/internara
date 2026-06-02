<?php

declare(strict_types=1);

namespace App\Domain\Setup\Console\Commands;

use App\Domain\Core\Data\AuditReport;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\Setup\Actions\GenerateSetupTokenAction;
use App\Domain\Setup\Actions\InstallSystemAction;
use App\Domain\Setup\Console\Commands\Traits\InteractsWithInstallerCli;
use App\Domain\Setup\Services\EnvironmentAuditor;
use App\Domain\Setup\Support\SystemProvisioner;
use App\Providers\DomainServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;

class SetupInstallCommand extends Command
{
    use InteractsWithInstallerCli;

    protected $signature = 'setup:install
        {--force : Force installation even if already installed}
        {--check-only : Run environment audit without provisioning}
        {--url= : The application URL (e.g., https://internara.example.com)}';

    public function __construct(
        private EnvironmentAuditor $auditor,
        private SystemProvisioner $provisioner,
        private GenerateSetupTokenAction $generateToken,
        private InstallSystemAction $installSystem,
    ) {
        parent::__construct();
        $this->description = __('setup.cli.starting_installation');
    }

    public function handle(): int
    {
        $this->displayBanner();

        try {
            $isInstalled = $this->isInstalled();

            if ($isInstalled && ! $this->option('force')) {
                $this->displayError(__('setup.cli.already_installed'));
                $this->line('  '.__('setup.cli.try_health_check'));

                return self::FAILURE;
            }

            if ($this->option('force')) {
                $this->components->warn(__('setup.cli.force_warning'));

                $allowed = config('setup.force_allowed_environments', ['local', 'dev', 'development', 'testing']);
                if (! in_array(app()->environment(), $allowed, true)) {
                    $this->displayError(__('setup.cli.force_restricted'));

                    return self::FAILURE;
                }

                if (app()->resolved('session.store')) {
                    session()->forget([
                        'setup.authorized', 'setup.token', 'setup.token_input',
                        'setup.form_data', 'setup.completed',
                    ]);
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

                $this->displayError(__('setup.cli.audit_failed'));

                return self::FAILURE;
            }

            if ($this->option('check-only')) {
                $this->line('  <fg=green>'.__('setup.cli.check_only_complete').'</>');

                return self::SUCCESS;
            }

            if ($url = $this->option('url')) {
                $this->setAppUrl($url);
                $this->line('  <fg=green>'.__('setup.cli.app_url_set', ['url' => $url]).'</>');
            } elseif ($this->isLocalhostUrl()) {
                $this->components->warn(__('setup.cli.app_url_warning'));
                $this->line('  '.__('setup.cli.app_url_hint'));
            }

            if (! $this->option('force') && ! $this->confirmProceed()) {
                error(__('setup.cli.aborted'));

                return self::FAILURE;
            }

            $this->displaySection(__('setup.cli.starting_installation'));

            SmartLogger::info(__('setup.cli.starting_installation'))
                ->module('setup')
                ->event('installation.started')
                ->save();

            $force = (bool) $this->option('force');

            foreach ($this->provisioner->getTasks() as $task => $label) {
                $this->components->task(
                    $label,
                    fn () => $this->provisioner->executeTask($task, $force),
                );
            }

            $tokenData = $this->generateToken->execute();

            $this->newLine();
            $this->components->task(
                __('setup.cli.tasks.optimize'),
                fn () => $this->runOptimization(),
            );

            $this->newLine();
            $provider = app()->getProvider(DomainServiceProvider::class);
            $this->components->task(__('setup.cli.tasks.discover_livewire'), fn () => $provider->discoverLivewireComponents());
            $this->components->task(__('setup.cli.tasks.discover_policies'), fn () => $provider->discoverPolicies());
            $this->components->task(__('setup.cli.tasks.discover_views'), fn () => $provider->registerBladeNamespaces());

            $this->displaySuccess($tokenData['plaintext'], $tokenData['expires_at']);

            $this->warnTemplateEnvValues();

            $this->displaySection(__('setup.cli.next_steps'));
            $this->line('  <fg=gray>'.__('setup.cli.start_server').'</>');
            $this->line('  <fg=gray>'.__('setup.cli.open_browser').'</>');

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
            $this->line('<fg=green;options=bold>  '.$category->label().'</>');

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
        $this->line('<fg=white;options=bold>  '.__('setup.cli.quick_access').'</>');
        $this->line('  <fg=cyan;options=bold,underscore>'.$signedUrl.'</>');
        $this->line('  <fg=gray>'.__('setup.cli.url_warning').'</>');

        $this->newLine();
        $this->line('<fg=white;options=bold>  '.__('setup.cli.manual_entry').'</>');
        $this->line('  '.__('setup.cli.visit_url_alt').': <fg=white;options=bold>'.route('setup').'</>');
        $this->line('  '.__('setup.cli.enter_code').": <fg=white;options=bold>{$token}</>");

        $this->newLine();
        $remainingMinutes = max(1, $expiresAt->diffInRealMinutes(now()));
        $this->line('  '.__('setup.cli.token_expires').": <fg=yellow>{$expiresAt->format('H:i:s T')} (".__('setup.cli.expires_in_minutes', ['count' => $remainingMinutes]).')</>');
    }

    protected function handleError(\Throwable $e): void
    {
        $this->newLine();
        $this->displayError($e->getMessage());

        if ($this->option('verbose')) {
            $this->line('<fg=gray>'.$e->getTraceAsString().'</>');
        }
    }

    private function warnTemplateEnvValues(): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            return;
        }

        $content = File::get($envPath);
        $templatePatterns = [
            'APP_URL' => ['your-domain.com'],
            'DB_PASSWORD' => ['your-password', 'secret'],
            'MAIL_USERNAME' => ['your-email@domain.com'],
            'MAIL_PASSWORD' => ['your-password'],
            'MAIL_FROM_ADDRESS' => ['noreply@domain.com', 'hello@example.com'],
        ];

        $found = [];

        foreach ($templatePatterns as $key => $patterns) {
            if (preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $content, $matches)) {
                $value = trim($matches[1]);

                foreach ($patterns as $pattern) {
                    if (str_contains($value, $pattern)) {
                        $found[] = $key;
                        break;
                    }
                }
            }
        }

        if ($found !== []) {
            $this->components->warn(__('setup.cli.template_env_warning'));
            $this->line('  '.__('setup.cli.template_env_vars').': '.implode(', ', $found));
            $this->line('  '.__('setup.cli.template_env_fix'));
        }
    }

    private function runOptimization(): void
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        Artisan::call('event:cache');

        Container::setInstance($this->laravel);
        Facade::setFacadeApplication($this->laravel);
        Model::setConnectionResolver($this->laravel['db']);
    }

    private function isLocalhostUrl(): bool
    {
        $url = config('app.url', 'http://localhost');

        return str_contains($url, 'localhost')
            || str_contains($url, '127.0.0.1')
            || str_contains($url, 'your-domain.com');
    }

    private function setAppUrl(string $url): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            return;
        }

        $content = File::get($envPath);

        if (preg_match('/^APP_URL=.*$/m', $content)) {
            $content = preg_replace('/^APP_URL=.*$/m', "APP_URL={$url}", $content);
        } else {
            $content .= "\nAPP_URL={$url}\n";
        }

        File::put($envPath, $content);
    }
}
