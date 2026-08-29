<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Backups\Actions;

use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\SysAdmin\Domain\Backups\Enums\BackupStatus;
use App\Modules\SysAdmin\Domain\Backups\Models\Backup;

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
