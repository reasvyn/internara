<?php

declare(strict_types=1);

namespace App\User\Dashboard\Listeners;

use App\Academics\AcademicYear\Events\AcademicYearActivated;
use App\Academics\AcademicYear\Events\AcademicYearCreated;
use Illuminate\Support\Facades\Cache;

final class ClearDashboardCacheOnYearChange
{
    public function handle(AcademicYearCreated|AcademicYearActivated $event): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}