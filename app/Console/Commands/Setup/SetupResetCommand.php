<?php

declare(strict_types=1);

namespace App\Console\Commands\Setup;

use App\Domain\Core\Support\AppInfo;
use App\Domain\Setup\Services\SetupService;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\warning;

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
        intro('SETUP RESET UTILITY ('.AppInfo::version().')');

        // Check if actually installed
        if (! $setupService->isInstalled()) {
            warning(__('setup.reset.not_installed'));
            note(__('setup.reset.new_token_generated'));

            $token = $setupService->generateToken();
            info($token);

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            warning(__('setup.reset.warning_lock_file'));
            note(__('setup.reset.warning_records'));

            if (! confirm(__('setup.reset.confirm_proceed'), false)) {
                error(__('setup.reset.aborted'));

                return self::FAILURE;
            }
        }

        // Remove lock file and generate new token
        $token = $setupService->reset();

        $this->newLine();
        outro(__('setup.reset.success'));

        $signedUrl = route('setup', ['setup_token' => $token]);

        info('URL: <fg=cyan;options=bold,underscore>'.$signedUrl.'</>');
        note('Token: <fg=white;options=bold>'.$token.'</>');
        warning(__('setup.reset.migration_note'));

        return self::SUCCESS;
    }
}
