<?php

declare(strict_types=1);

namespace App\User\Actions;

use App\Core\Actions\BaseReadAction;
use App\Core\Models\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ReadActivityLogAction extends BaseReadAction
{
    public function execute(string $userId, int $perPage = 50): LengthAwarePaginator
    {
        return ActivityLog::forUser($userId)->latest()->paginate($perPage);
    }
}
