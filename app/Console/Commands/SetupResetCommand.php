<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Setup\SetupService;
use App\Support\AppInfo;
use Illuminate\Console\Command;

/**
 * Emergency reset of setup state.
 *
 * S1 - Secure: Requires --force flag in production. Removes lock file and clears session state.
 */
class SetupResetCommand extends Command
{
    protected $signature = 'setup:reset {--force : Force reset without confirmation}';

    protected $description = 'Reset the setup state and allow re-running the setup wizard';

    public function handle(SetupService $setupService): int
    {
        $this->displayBanner();

        // Check if actually installed
        if (! $setupService->isInstalled()) {
            $this->warn('Setup has not been completed. No reset needed.');
            $this->info('A new setup token has been generated:');
            $this->line('  ' . $setupService->generateToken());

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->error('This will REMOVE the installation lock file and allow the setup wizard to run again.');
            $this->warn('Existing database records will NOT be removed. Run migrations manually if needed.');

            if (! $this->confirm('Do you want to proceed?', false)) {
                $this->warn('Reset aborted.');

                return self::FAILURE;
            }
        }

        // Remove lock file and generate new token
        $token = $setupService->reset();

        $this->newLine();
        $this->components->info('Setup state has been reset.');
        $this->line('Setup token: <fg=cyan>' . $token . '</>');
        $this->line('Visit: <fg=cyan>' . route('setup', ['setup_token' => $token]) . '</>');
        $this->warn('Note: Existing database records are not removed. Run `php artisan migrate:fresh` if needed.');

        return self::SUCCESS;
    }

    protected function displayBanner(): void
    {
        $this->newLine();
        $this->line(' <fg=white;bg=red;options=bold> SETUP RESET </> <fg=red;options=bold>UTILITY</>');
        $this->line(' <fg=gray>Version: ' . AppInfo::version() . '</>');
        $this->newLine();
    }
}
