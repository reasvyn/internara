<?php

declare(strict_types=1);

namespace App\SysAdmin\Observability\Console\Commands;

use App\SysAdmin\Observability\Recorders\RegistrationRecorder;
use App\SysAdmin\Observability\Recorders\SystemRecorder;
use Illuminate\Console\Command;

class PulseRecordSnapshotsCommand extends Command
{
    protected $signature = 'pulse:record-snapshots';

    protected $description = 'Record Internara-specific Pulse snapshots for custom dashboard cards';

    public function handle(): int
    {
        $this->components->info(__('sysadmin.pulse_record.started'));

        RegistrationRecorder::recordSnapshot();
        SystemRecorder::recordSnapshot();

        $this->components->info(__('sysadmin.pulse_record.completed'));

        return Command::SUCCESS;
    }
}
