<?php

declare(strict_types=1);

namespace App\User\UserManagement\Actions;

use App\Core\Actions\BaseCommandAction;
use App\User\Jobs\ArchiveStudentAccountsJob;
use Illuminate\Database\Eloquent\Builder;

final class DispatchArchiveStudentAccountsAction extends BaseCommandAction
{
    /**
     * Above this many matching students, archival is queued instead of run
     * inline (E1MSJ NFR-R2). Below it, the synchronous chunked path (UC-3)
     * is preserved.
     */
    public const int QUEUE_THRESHOLD = 500;

    public function __construct(
        protected readonly ArchiveStudentAccountsAction $archiveAction,
    ) {}

    public function execute(Builder $query, ?int $queueThreshold = null): int
    {
        $threshold = $queueThreshold ?? self::QUEUE_THRESHOLD;
        $count = (clone $query)->count();

        if ($count <= $threshold) {
            return $this->archiveAction->execute($query);
        }

        ArchiveStudentAccountsJob::dispatch(
            studentIds: (clone $query)->pluck('users.id')->all(),
        );

        $this->log('student_accounts_archive_queued', null, ['count' => $count]);

        return $count;
    }
}
