<?php

declare(strict_types=1);

namespace App\User\Actions;

use App\Core\Models\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class GetActivityLogsAction
{
    public function execute(string $userId, int $perPage = 50): LengthAwarePaginator
    {
        return ActivityLog::forUser($userId)->latest()->paginate($perPage);
    }
}
