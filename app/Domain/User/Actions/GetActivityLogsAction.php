<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Models\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class GetActivityLogsAction extends BaseAction
{
    public function execute(string $userId, int $perPage = 50): LengthAwarePaginator
    {
        return ActivityLog::causedBy($userId)
            ->latest()
            ->paginate($perPage);
    }
}
