<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Backup\Listeners;

use App\Modules\SysAdmin\Domain\Backup\Events\BackupFailed;
use App\Modules\SysAdmin\Domain\Backup\Notifications\BackupFailedNotification;
use App\Modules\User\Models\User;

final class SendBackupFailedNotification
{
    public function handle(BackupFailed $event): void
    {
        $superAdmins = User::role('superadmin')->get();

        foreach ($superAdmins as $admin) {
            $admin->notify(new BackupFailedNotification($event->backup));
        }
    }
}
