<?php

declare(strict_types=1);

namespace App\SysAdmin\Console\Commands;

use Illuminate\Console\Command;

class ShowRecoveryPathCommand extends Command
{
    protected $signature = 'admin:recovery-path';

    public function __construct()
    {
        parent::__construct();
        $this->description = __('sysadmin.recovery_path.description');
    }

    public function handle(): int
    {
        $path = storage_path('app/private/.recovery-key');

        $this->components->info(__('sysadmin.recovery_path.info'));
        $pathLabel = __('sysadmin.recovery_path.info');
        $this->line("  <fg=cyan>{$path}</>");
        $this->newLine();

        if (file_exists($path)) {
            $this->components->twoColumnDetail(
                __('sysadmin.recovery_path.status'),
                '<fg=green>'.__('sysadmin.recovery_path.exists').'</>',
            );
        } else {
            $this->components->twoColumnDetail(
                __('sysadmin.recovery_path.status'),
                '<fg=yellow>'.__('sysadmin.recovery_path.missing').'</>',
            );
        }

        return self::SUCCESS;
    }
}
