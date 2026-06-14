<?php

declare(strict_types=1);

namespace App\SysAdmin\Backups\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\SysAdmin\Backups\Enums\BackupStatus;
use App\SysAdmin\Backups\Enums\BackupType;
use App\SysAdmin\Backups\Events\BackupCompleted;
use App\SysAdmin\Backups\Events\BackupFailed;
use App\SysAdmin\Backups\Models\Backup;
use App\SysAdmin\Backups\Support\BackupRunner;
use App\User\Models\User;

final class CreateBackupAction extends BaseCommandAction
{
    public function __construct(protected readonly BackupRunner $runner) {}

    public function execute(BackupType $type, ?User $user = null): Backup
    {
        return $this->transaction(function () use ($type, $user) {
            $backup = Backup::create([
                'type' => $type->value,
                'status' => BackupStatus::RUNNING->value,
                'created_by' => $user?->id,
                'started_at' => now(),
            ]);

            try {
                $filePath = match ($type) {
                    BackupType::DATABASE => $this->runner->runDatabaseDump(),
                    BackupType::STORAGE => $this->runner->runStorageDump(),
                    BackupType::BOTH => $this->runner->runCombinedDump(),
                };

                $backup->update([
                    'file_path' => $filePath,
                    'file_size' => $this->runner->fileSize($filePath),
                    'status' => BackupStatus::COMPLETED->value,
                    'completed_at' => now(),
                ]);

                $this->log('backup_created', $backup, [
                    'type' => $type->value,
                    'file_size' => $backup->file_size,
                ]);

                event(new BackupCompleted($backup));

                return $backup;
            } catch (\Throwable $e) {
                $backup->update([
                    'status' => BackupStatus::FAILED->value,
                    'error_output' => $e->getMessage(),
                    'completed_at' => now(),
                ]);

                $this->log('backup_failed', $backup, [
                    'type' => $type->value,
                    'error' => $e->getMessage(),
                ]);

                event(new BackupFailed($backup));

                throw new RejectedException(__('backups.create_failed').': '.$e->getMessage());
            }
        });
    }
}
