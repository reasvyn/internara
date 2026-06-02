<?php

declare(strict_types=1);

namespace App\Domain\Setup\Console\Commands;

use App\Domain\Core\Support\SmartLogger;
use App\Domain\Setup\Actions\GenerateSetupTokenAction;
use App\Domain\Setup\Console\Commands\Traits\InteractsWithInstallerCli;
use App\Domain\Setup\Models\Setup;
use Illuminate\Console\Command;

class SetupResetCommand extends Command
{
    use InteractsWithInstallerCli;

    protected $signature = 'setup:reset
                            {--force : Skip the isInstalled guard — use when installation is corrupted}';

    public function __construct()
    {
        parent::__construct();
        $this->description = __('setup.reset.description');
    }

    public function handle(): int
    {
        $this->displayBanner();

        if (Setup::state()->isInstalled() && ! $this->option('force')) {
            $this->displayError(__('setup.reset.protected'));
            $this->line('  '.__('setup.cli.try_health_check'));

            SmartLogger::info(__('setup.reset.protected'))
                ->module('setup')
                ->event('reset.blocked')
                ->save();

            return self::FAILURE;
        }

        $result = app(GenerateSetupTokenAction::class)->execute();
        $signedUrl = route('setup', ['setup_token' => $result['plaintext']]);

        $this->displaySection(__('setup.reset.new_token_generated'));
        $this->newLine();
        $this->line('<fg=white;options=bold>  '.__('setup.cli.quick_access').'</>');
        $this->line('  <fg=cyan;options=bold,underscore>'.$signedUrl.'</>');
        $this->line('  <fg=gray>'.__('setup.cli.url_warning').'</>');

        $this->newLine();
        $this->line('<fg=white;options=bold>  '.__('setup.cli.manual_entry').'</>');
        $this->line('  '.__('setup.cli.visit_url_alt').': <fg=white;options=bold>'.route('setup').'</>');
        $this->line('  '.__('setup.cli.enter_code').": <fg=white;options=bold>{$result['plaintext']}</>");

        $this->newLine();
        $remainingMinutes = max(1, $result['expires_at']->diffInRealMinutes(now()));
        $this->line('  '.__('setup.cli.token_expires').": <fg=yellow>{$result['expires_at']->format('H:i:s T')} (".__('setup.cli.expires_in_minutes', ['count' => $remainingMinutes]).')</>');

        SmartLogger::info(__('setup.reset.success'))
            ->module('setup')
            ->event('reset.completed')
            ->save();

        return self::SUCCESS;
    }
}
