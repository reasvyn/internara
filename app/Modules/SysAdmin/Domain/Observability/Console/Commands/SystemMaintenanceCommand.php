<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Observability\Console\Commands;

use Illuminate\Console\Command;

final class SystemMaintenanceCommand extends Command
{
    protected $signature = 'system:maintenance
        {--on : Enable maintenance mode}
        {--off : Disable maintenance mode}
        {--reason= : Reason shown on the maintenance notice}';

    protected $description = 'Open or close the system maintenance window';

    public function handle(): int
    {
        if ($this->option('on') === $this->option('off')) {
            $this->error('Choose exactly one of --on or --off.');

            return self::INVALID;
        }

        if ($this->option('off')) {
            $this->laravel->maintenanceMode()->deactivate();
            $this->info(__('sysadmin.maintenance.closed'));

            return self::SUCCESS;
        }

        $reason = trim((string) ($this->option('reason') ?? ''));
        if ($reason === '') {
            $this->error(__('sysadmin.maintenance.reason_required'));

            return self::INVALID;
        }

        $this->laravel->maintenanceMode()->activate([
            'except' => [],
            'redirect' => null,
            'retry' => null,
            'refresh' => null,
            'secret' => null,
            'status' => 503,
            'template' => view('errors.maintenance', ['reason' => $reason])->render(),
        ]);
        $this->info(__('sysadmin.maintenance.opened'));

        return self::SUCCESS;
    }
}
