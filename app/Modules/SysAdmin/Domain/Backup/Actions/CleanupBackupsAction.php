<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Backup\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\SysAdmin\Domain\Backup\Models\Backup;
use App\Modules\SysAdmin\Domain\Backup\Services\BackupRunner;

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
                ->chunk(100, function ($backup) use (&$deleted) {
                    foreach ($backup as $backup) {
                        if ($backup->file_path) {
                            $this->runner->deleteFile($backup->file_path);
                        }

                        $backup->delete();
                        $deleted++;
                    }
                });

            if ($deleted > 0) {
                $this->log('backup_cleaned', null, [
                    'retention_days' => $retentionDays,
                    'deleted_count' => $deleted,
                ]);
            }

            return $deleted;
        });
    }
}
