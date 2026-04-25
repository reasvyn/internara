<?php

declare(strict_types=1);

namespace Modules\Setup\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Setting\Services\Contracts\SettingService;

/**
 * Emergency command to reset the application setup state.
 * This command bypasses the setup lockdown by clearing installation flags and regenerating the setup token.
 */
class SetupResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-reset {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Emergency reset of setup state to bypass lockdown';

    /**
     * Create a new command instance.
     */
    public function __construct(protected SettingService $settingService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->components->warn('RECOVERY: System Initialization Reset');

        // [S1 - Secure] Production Safeguard
        if (app()->environment('production') && ! $this->option('force')) {
            $this->components->error('System is in PRODUCTION. Resetting setup is highly destructive and requires the --force flag.');
            
            return self::FAILURE;
        }

        if (! $this->confirmReset()) {
            return self::FAILURE;
        }

        $this->components->task('De-authorizing installation status', function () {
            $this->settingService->setValue('app_installed', false);
            
            // [S3 - Scalable] Clear relevant caches
            Cache::forget('user.super_admin');
            Cache::forget('internara.installed');

            // [S2 - Sustain] Recursive step cleanup
            $steps = ['welcome', 'environment', 'school', 'account', 'department', 'internship', 'system', 'complete'];
            foreach ($steps as $step) {
                $this->settingService->setValue("setup_step_{$step}", false);
            }
        });

        $token = Str::random(64); // Increased entropy for enterprise security
        $this->components->task('Regenerating sovereign setup token', function () use ($token) {
            $this->settingService->setValue('setup_token', $token);
        });

        // [S2 - Sustain] Audit Logging
        activity('setup')
            ->event('emergency_reset')
            ->withProperties(['method' => 'CLI', 'env' => app()->environment()])
            ->log('System setup state has been reset via emergency CLI command.');

        $this->newLine();
        $this->components->info('Success: Setup infrastructure has been unlocked.');

        $setupUrl = route('setup.welcome', ['token' => $token]);
        $this->info('One-time secure access link generated:');
        $this->warn($setupUrl);
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Confirm if the reset should proceed.
     */
    protected function confirmReset(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        return $this->confirm(
            'This will unlock the setup routes and allow reconfiguration. Continue?',
            false,
        );
    }
}
