<?php

declare(strict_types=1);

namespace App\SysAdmin\Backups\Actions;

use App\Core\Actions\BaseCommandAction;
use App\SysAdmin\Backups\Models\Backup;
use App\SysAdmin\Backups\Services\BackupRunner;

final class CleanupBackupsAction extends BaseCommandAction
{
    public function __construct(protected readonly BackupRunner $runner) {}

    public function execute(int $retentionDays = 30): int
    {
        return $this->transaction(function () use ($retentionDays) {
            $cutoff = now()->subDays($retentionDays);
            $deleted = 0;

            Backup::where('status', 'completed')
                ->where('created_at', '<', $cutoff)
                ->chunk(100, function ($backups) use (&$deleted) {
                    foreach ($backups as $backup) {
                        if ($backup->file_path) {
                            $this->runner->deleteFile($backup->file_path);
                        }

                        $backup->delete();
                        $deleted++;
                    }
                });

            if ($deleted > 0) {
                $this->log('backups_cleaned', null, [
                    'retention_days' => $retentionDays,
                    'deleted_count' => $deleted,
                ]);
            }

            return $deleted;
        });
    }
}
