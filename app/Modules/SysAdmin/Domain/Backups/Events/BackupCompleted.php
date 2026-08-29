<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Backups\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\SysAdmin\Domain\Backups\Models\Backup;

final class BackupCompleted extends BaseEvent
{
    public function __construct(public readonly Backup $backup) {}

    public function eventName(): string
    {
        return 'backup.completed';
    }
}
