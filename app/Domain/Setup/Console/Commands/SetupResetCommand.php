<?php

declare(strict_types=1);

namespace App\Domain\Setup\Console\Commands;

use App\Domain\Core\Support\SmartLogger;
use App\Domain\Setup\Actions\GenerateSetupTokenAction;
use App\Domain\Setup\Console\Commands\Traits\InteractsWithInstallerCli;
use App\Domain\Setup\Models\Setup;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\note;

class SetupResetCommand extends Command
{
    use InteractsWithInstallerCli;

    protected $signature = 'setup:reset';

    public function __construct()
    {
        parent::__construct();
        $this->description = __('setup.reset.description');
    }

    public function handle(): int
    {
        $this->displayBanner();

        if (Setup::state()->isInstalled()) {
            error(__('setup.reset.protected'));

            $this->info(__('setup.cli.try_health_check'));

            SmartLogger::info(__('setup.reset.protected'))
                ->module('setup')
                ->event('reset.blocked')
                ->save();

            return self::FAILURE;
        }

        $result = app(GenerateSetupTokenAction::class)->execute();
        $signedUrl = route('setup', ['setup_token' => $result['plaintext']]);
        note("URL: <fg=cyan;options=bold,underscore>{$signedUrl}</>");
        note("Token: <fg=white;options=bold>{$result['plaintext']}</>");
        note("Expires: {$result['expires_at']->format('H:i:s T')} (in {$result['expires_at']->diffForHumans()})");

        return self::SUCCESS;
    }
}
