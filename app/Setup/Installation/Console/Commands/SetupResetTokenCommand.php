<?php

declare(strict_types=1);

namespace App\Setup\Installation\Console\Commands;

use App\Core\Services\SmartLogger;
use App\Setup\Entities\SetupEntity;
use App\Setup\Installation\Actions\GenerateSetupTokenAction;
use App\Setup\Installation\Console\Commands\Concerns\InteractsWithInstallerCli;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class SetupResetTokenCommand extends Command
{
    use InteractsWithInstallerCli;

    protected $signature = 'setup:reset-token';

    public function __construct(private GenerateSetupTokenAction $generateToken)
    {
        parent::__construct();
        $this->description = __('setup.reset_token.description');
    }

    public function handle(): int
    {
        $this->displayBanner();

        if (! Schema::hasTable('settings')) {
            $this->displayError(__('setup.reset_token.table_missing'));
            $this->line('  '.__('setup.reset_token.table_missing_hint'));

            return self::FAILURE;
        }

        if (SetupEntity::get()->isInstalled()) {
            $this->displayError(__('setup.reset_token.protected'));
            $this->line('  '.__('setup.cli.try_health_check'));

            SmartLogger::info(__('setup.reset_token.protected'))
                ->module('setup')
                ->event('reset.blocked')
                ->withPiiMasking()
                ->save();

            return self::FAILURE;
        }

        $result = $this->generateToken->execute();
        $signedUrl = route('setup', ['setup_token' => $result->plaintext]);

        $this->displaySection(__('setup.reset_token.new_token_generated'));
        $this->newLine();
        $this->line('<fg=white;options=bold>  '.__('setup.cli.quick_access').'</>');
        $this->line('  <fg=cyan;options=bold,underscore>'.$signedUrl.'</>');
        $this->line('  <fg=gray>'.__('setup.cli.url_warning').'</>');

        $this->newLine();
        $this->line('<fg=white;options=bold>  '.__('setup.cli.manual_entry').'</>');
        $this->line(
            '  '.
                __('setup.cli.visit_url_alt').
                ': <fg=white;options=bold>'.
                route('setup').
                '</>',
        );
        $this->line(
            '  '.__('setup.cli.enter_code').": <fg=white;options=bold>{$result->plaintext}</>",
        );

        $this->newLine();
        $remainingMinutes = max(1, $result->expiresAt->diffInUTCMinutes(now()));
        $this->line(
            '  '.
                __('setup.cli.token_expires').
                ": <fg=yellow>{$result->expiresAt->format('H:i:s T')} (".
                __('setup.cli.expires_in_minutes', ['count' => $remainingMinutes]).
                ')</>',
        );

        SmartLogger::info(__('setup.reset_token.success'))
            ->module('setup')
            ->event('reset.completed')
            ->withPiiMasking()
            ->save();

        return self::SUCCESS;
    }
}
