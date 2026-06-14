<?php

declare(strict_types=1);

namespace App\SysAdmin\Backups\Listeners;

use App\SysAdmin\Backups\Events\BackupFailed;
use App\SysAdmin\Backups\Notifications\BackupFailedNotification;
use App\User\Models\User;

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
