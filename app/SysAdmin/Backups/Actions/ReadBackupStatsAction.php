<?php

declare(strict_types=1);

namespace App\SysAdmin\Backups\Actions;

use App\Core\Actions\BaseReadAction;
use App\SysAdmin\Backups\Enums\BackupStatus;
use App\SysAdmin\Backups\Models\Backup;

final class ReadBackupStatsAction extends BaseReadAction
{
    public function execute(): array
    {
        return [
            'total' => Backup::count(),
            'completed' => Backup::where('status', BackupStatus::COMPLETED->value)->count(),
            'failed' => Backup::where('status', BackupStatus::FAILED->value)->count(),
            'latest' => Backup::where('status', BackupStatus::COMPLETED->value)->latest()->first(),
        ];
    }
}
