<?php

declare(strict_types=1);

namespace App\User\Dashboard\Listeners;

use App\Academics\Department\Events\DepartmentCreated;
use App\Academics\Department\Events\DepartmentDeleted;
use Illuminate\Support\Facades\Cache;

final class ClearDashboardCacheOnDepartmentChange
{
    public function handle(DepartmentCreated|DepartmentDeleted $event): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}