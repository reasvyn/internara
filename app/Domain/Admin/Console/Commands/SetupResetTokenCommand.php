<?php

declare(strict_types=1);

namespace App\Domain\Admin\Console\Commands;

use App\Domain\Admin\Aggregates\Setup\Actions\GenerateSetupTokenAction;
use App\Domain\Admin\Aggregates\Setup\Models\Setup;
use App\Domain\Admin\Console\Commands\Traits\InteractsWithInstallerCli;
use App\Domain\Core\Support\SmartLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SetupResetTokenCommand extends Command
{
    use InteractsWithInstallerCli;

    protected $signature = 'setup:reset-token';

    public function __construct()
    {
        parent::__construct();
        $this->description = __('setup.reset_token.description');
    }

    public function handle(): int
    {
        $this->displayBanner();

        if (! Schema::hasTable('setups')) {
            $this->displayError(__('setup.reset_token.table_missing'));
            $this->line('  '.__('setup.reset_token.table_missing_hint'));

            return self::FAILURE;
        }

        if (Setup::state()->isInstalled()) {
            $this->displayError(__('setup.reset_token.protected'));
            $this->line('  '.__('setup.cli.try_health_check'));

            SmartLogger::info(__('setup.reset_token.protected'))
                ->module('setup')
                ->event('reset.blocked')
                ->save();

            return self::FAILURE;
        }

        $result = app(GenerateSetupTokenAction::class)->execute();
        $signedUrl = route('setup', ['setup_token' => $result['plaintext']]);

        $this->displaySection(__('setup.reset_token.new_token_generated'));
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

        SmartLogger::info(__('setup.reset_token.success'))
            ->module('setup')
            ->event('reset.completed')
            ->save();

        return self::SUCCESS;
    }
}
