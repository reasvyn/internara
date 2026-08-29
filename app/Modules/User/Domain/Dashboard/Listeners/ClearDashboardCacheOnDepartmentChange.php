<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Dashboard\Listeners;

use App\Modules\Academics\Domain\Department\Events\DepartmentCreated;
use App\Modules\Academics\Domain\Department\Events\DepartmentDeleted;
use App\Modules\Academics\Domain\Department\Events\DepartmentUpdated;
use Illuminate\Support\Facades\Cache;

final class ClearDashboardCacheOnDepartmentChange
{
    public function handle(DepartmentCreated|DepartmentDeleted|DepartmentUpdated $event): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
