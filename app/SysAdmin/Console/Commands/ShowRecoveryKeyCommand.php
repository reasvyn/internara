<?php

declare(strict_types=1);

namespace App\SysAdmin\Console\Commands;

use App\Core\Support\SmartLogger;
use App\Setup\Entities\SetupEntity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ShowRecoveryKeyCommand extends Command
{
    protected $signature = 'admin:recovery-show';

    public function __construct()
    {
        parent::__construct();
        $this->description = __('sysadmin.recovery_show.description');
    }

    public function handle(): int
    {
        $path = storage_path('app/private/.recovery-key');

        if (! File::exists($path)) {
            $this->components->error(__('sysadmin.recovery_path.missing'));

            return self::FAILURE;
        }

        $recoveryKey = SetupEntity::get()->recoveryKey();

        if (! $recoveryKey) {
            $this->components->warn(__('sysadmin.recovery_show.no_setup'));

            return self::FAILURE;
        }

        $this->components->warn(__('sysadmin.recovery_show.warning'));

        $confirmed = $this->components->confirm(
            question: __('sysadmin.recovery_show.confirm'),
            default: false,
        );

        if (! $confirmed) {
            $this->components->info(__('sysadmin.recovery_show.aborted'));

            return self::SUCCESS;
        }

        $content = File::get($path);

        SmartLogger::info(__('log.recovery_key_viewed_cli'))
            ->module('admin')
            ->event('recovery_key.viewed')
            ->systemOnly()
            ->save();

        $this->newLine();
        $this->line('  <fg=yellow>'.__('sysadmin.recovery_show.key_label').'</>');
        $this->line('  <fg=white;bg=yellow> '.$recoveryKey.' </>');
        $this->newLine();

        return self::SUCCESS;
    }
}
