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
        $this->components->warn('EMERGENCY: Setup Recovery Mode');

        if (! $this->confirmReset()) {
            return self::FAILURE;
        }

        $this->components->task('Clearing installation flags', function () {
            $this->settingService->setValue('app_installed', false);
            Cache::forget('user.super_admin');

            // Optional: Clear step progress to allow a clean restart
            $steps = [
                'welcome',
                'environment',
                'school',
                'account',
                'department',
                'internship',
                'system',
            ];
            foreach ($steps as $step) {
                $this->settingService->setValue("setup_step_{$step}", false);
            }
        });

        $token = Str::random(32);
        $this->components->task('Regenerating setup token', function () use ($token) {
            $this->settingService->setValue('setup_token', $token);
        });

        $this->newLine();
        $this->components->info('Setup state has been reset successfully!');

        $setupUrl = route('setup.welcome', ['token' => $token]);
        $this->info('You can now access the Setup Wizard again using the link below:');
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
