<?php

declare(strict_types=1);

namespace App\SysAdmin\Backups\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\SysAdmin\Backups\Models\Backup;
use App\SysAdmin\Backups\Services\BackupRunner;

final class DeleteBackupAction extends BaseCommandAction
{
    public function __construct(protected readonly BackupRunner $runner) {}

    public function execute(Backup $backup): void
    {
        if (! $backup->asBackupState()->isDeletable()) {
            throw new RejectedException(__('backups.cannot_delete_active'));
        }

        $this->transaction(function () use ($backup) {
            if ($backup->file_path) {
                $this->runner->deleteFile($backup->file_path);
            }

            $backup->delete();

            $this->log('backup_deleted', $backup, [
                'type' => $backup->type,
                'file_size' => $backup->file_size,
            ]);
        });
    }
}
