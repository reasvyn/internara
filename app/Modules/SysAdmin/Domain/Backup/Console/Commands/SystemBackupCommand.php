<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Backup\Console\Commands;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\SysAdmin\Domain\Backup\Actions\CleanupBackupsAction;
use App\Modules\SysAdmin\Domain\Backup\Actions\CreateBackupAction;
use App\Modules\SysAdmin\Domain\Backup\Enums\BackupType;
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
            $this->warn(__('backup.disabled'));

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

        $this->info(__('backup.starting', ['type' => $backupType->label()]));

        try {
            $backup = $createBackup->execute($backupType);
            $this->info(__('backup.completed', [
                'size' => $backup->asBackupState()->formattedSize(),
            ]));

            if ($this->option('cleanup')) {
                $retention = (int) config('backup.retention_days', 30);
                $deleted = $cleanupBackups->execute($retention);
                $this->info(__('backup.cleanup_completed', ['count' => $deleted]));
                if ($deleted > 0) {
                    $this->info(__('backup.old_backups_removed', ['count' => $deleted]));
                }
            }

            return Command::SUCCESS;
        } catch (RejectedException $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
