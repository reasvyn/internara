<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Backups\Listeners;

use App\Modules\SysAdmin\Domain\Backups\Events\BackupFailed;
use App\Modules\SysAdmin\Domain\Backups\Notifications\BackupFailedNotification;
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
