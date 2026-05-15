<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Setup\GenerateSetupTokenAction;
use App\Console\Commands\Setup\Traits\InteractsWithInstallerCli;
use App\Models\Setup;
use App\Support\SmartLogger;
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
