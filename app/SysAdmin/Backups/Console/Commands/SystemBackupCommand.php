<?php

declare(strict_types=1);

namespace App\SysAdmin\Backups\Console\Commands;

use App\Core\Exceptions\RejectedException;
use App\SysAdmin\Backups\Actions\CleanupBackupsAction;
use App\SysAdmin\Backups\Actions\CreateBackupAction;
use App\SysAdmin\Backups\Enums\BackupType;
use Illuminate\Console\Command;

final class SystemBackupCommand extends Command
{
    protected $signature = 'system:backup
        {--type= : Backup type: database, storage, or both}
        {--force : Skip pre-flight checks}
        {--cleanup : Run retention cleanup after backup}';

    protected $description = 'Run a system backup';

    public function handle(
        CreateBackupAction $createBackup,
        CleanupBackupsAction $cleanupBackups,
    ): int {
        if (! config('backup.enabled', false) && ! $this->option('force')) {
            $this->warn(__('backups.disabled'));

            return Command::SUCCESS;
        }

        $type = $this->option('type');
        $backupType = match ($type) {
            'database' => BackupType::DATABASE,
            'storage' => BackupType::STORAGE,
            null, 'both' => BackupType::BOTH,
            default => throw new \InvalidArgumentException(
                "Invalid type: {$type}. Use: database, storage, or both.",
            ),
        };

        $this->info(__('backups.starting', ['type' => $backupType->label()]));

        try {
            $backup = $createBackup->execute($backupType);
            $this->info(__('backups.completed', [
                'size' => $backup->asBackupState()->formattedSize(),
            ]));

            if ($this->option('cleanup')) {
                $retention = (int) config('backup.retention_days', 30);
                $deleted = $cleanupBackups->execute($retention);
                $this->info(__('backups.cleanup_completed', ['count' => $deleted]));
            }

            return Command::SUCCESS;
        } catch (RejectedException $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
