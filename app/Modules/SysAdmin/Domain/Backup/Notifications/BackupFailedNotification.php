<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Backup\Notifications;

use App\Modules\SysAdmin\Domain\Backup\Models\Backup;
use App\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class BackupFailedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Backup $backup) {}

    public function via(User $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'backup_id' => $this->backup->id,
            'type' => $this->backup->type,
            'error' => $this->backup->error_output,
            'message' => __('backup.notification_failed', [
                'type' => $this->backup->type,
            ]),
        ];
    }
}
