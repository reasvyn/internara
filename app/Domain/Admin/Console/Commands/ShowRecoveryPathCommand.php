<?php

declare(strict_types=1);

namespace App\Domain\Admin\Console\Commands;

use Illuminate\Console\Command;

class ShowRecoveryPathCommand extends Command
{
    protected $signature = 'admin:recovery-path';

    protected $description = 'Show the file path where the recovery key is stored';

    public function handle(): int
    {
        $path = storage_path('app/private/.recovery-key');

        $this->components->info(__('admin.recovery_path.info'));
        $this->line("  <fg=cyan>{$path}</>");

        if (file_exists($path)) {
            $this->components->twoColumnDetail(
                __('admin.recovery_path.status'),
                '<fg=green>'.__('admin.recovery_path.exists').'</>',
            );
        } else {
            $this->components->twoColumnDetail(
                __('admin.recovery_path.status'),
                '<fg=yellow>'.__('admin.recovery_path.missing').'</>',
            );
        }

        return self::SUCCESS;
    }
}
