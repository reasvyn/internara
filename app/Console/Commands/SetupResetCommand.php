<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Setup\GenerateSetupTokenAction;
use App\Actions\Setup\ResetSetupStateAction;
use App\Console\Commands\Setup\Traits\InteractsWithInstallerCli;
use App\Models\Setup;
use App\Support\Logger;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\warning;

class SetupResetCommand extends Command
{
    use InteractsWithInstallerCli;

    protected $signature = 'setup:reset {--force : Force reset without confirmation}';

    public function __construct()
    {
        parent::__construct();
        $this->description = __('setup.reset.warning_lock_file');
    }

    public function handle(ResetSetupStateAction $reset): int
    {
        $this->displayBanner();

        if (! Setup::isInstalled()) {
            Logger::info(__('setup.reset.not_installed'))
                ->module('setup')
                ->event('reset.skipped')
                ->save();

            warning(__('setup.reset.not_installed'));
            $result = app(GenerateSetupTokenAction::class)->execute();
            $signedUrl = route('setup', ['setup_token' => $result['plaintext']]);
            note("URL: <fg=cyan;options=bold,underscore>{$signedUrl}</>");
            note("Token: <fg=white;options=bold>{$result['plaintext']}</>");
            note("Expires: {$result['expires_at']->format('H:i:s')} (in {$result['expires_at']->diffForHumans()})");

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

        $result = $reset->execute();

        Logger::info(__('setup.reset.success'))
            ->module('setup')
            ->event('reset.completed')
            ->save();

        $this->newLine();
        outro(__('setup.reset.success'));

        $signedUrl = route('setup', ['setup_token' => $result['plaintext']]);

        info("URL: <fg=cyan;options=bold,underscore>{$signedUrl}</>");
        note("Token: <fg=white;options=bold>{$result['plaintext']}</>");
        note("Expires: {$result['expires_at']->format('H:i:s')} (in {$result['expires_at']->diffForHumans()})");
        warning(__('setup.reset.migration_note'));

        return self::SUCCESS;
    }
}
