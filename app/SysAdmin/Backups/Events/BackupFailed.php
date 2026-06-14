<?php

declare(strict_types=1);

namespace App\SysAdmin\Backups\Events;

use App\Core\Events\BaseEvent;
use App\SysAdmin\Backups\Models\Backup;

final class BackupFailed extends BaseEvent
{
    public function __construct(public readonly Backup $backup) {}

    public function eventName(): string
    {
        return 'backup.failed';
    }
}
