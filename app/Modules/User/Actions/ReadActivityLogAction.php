<?php

declare(strict_types=1);

namespace App\Modules\User\Actions;

use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\Core\Models\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ReadActivityLogAction extends BaseReadAction
{
    public function execute(string $userId, int $perPage = 50): LengthAwarePaginator
    {
        return ActivityLog::forUser($userId)->latest()->paginate($perPage);
    }
}
