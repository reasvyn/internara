<?php

declare(strict_types=1);

namespace App\Domain\Admin\Console\Commands;

use App\Domain\Admin\Recorders\RegistrationRecorder;
use App\Domain\Admin\Recorders\SystemRecorder;
use Illuminate\Console\Command;

class PulseRecordSnapshotsCommand extends Command
{
    protected $signature = 'pulse:record-snapshots';

    protected $description = 'Record Internara-specific Pulse snapshots for custom dashboard cards';

    public function handle(): int
    {
        $this->components->info(__('admin.pulse_record.started'));

        RegistrationRecorder::recordSnapshot();
        SystemRecorder::recordSnapshot();

        $this->components->info(__('admin.pulse_record.completed'));

        return Command::SUCCESS;
    }
}
