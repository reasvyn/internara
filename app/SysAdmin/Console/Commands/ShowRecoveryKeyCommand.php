<?php

declare(strict_types=1);

namespace App\SysAdmin\Console\Commands;

use App\Core\Support\SmartLogger;
use App\User\UserManagement\Actions\ReadRecoveryKeyAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ShowRecoveryKeyCommand extends Command
{
    protected $signature = 'admin:recovery-show';

    public function __construct(
        private readonly ReadRecoveryKeyAction $readRecoveryKey,
    ) {
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

        $recoveryKeyPlaintext = $this->readRecoveryKey->execute();

        if (! $recoveryKeyPlaintext) {
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

        SmartLogger::info(__('log.recovery_key_viewed_cli'))
            ->module('admin')
            ->event('recovery_key.viewed')
            ->withPiiMasking()
            ->systemOnly()
            ->save();

        $this->newLine();
        $this->line('  <fg=yellow>'.__('sysadmin.recovery_show.key_label').'</>');
        $this->line('  <fg=white;bg=yellow> '.$recoveryKeyPlaintext.' </>');
        $this->newLine();

        return self::SUCCESS;
    }
}
