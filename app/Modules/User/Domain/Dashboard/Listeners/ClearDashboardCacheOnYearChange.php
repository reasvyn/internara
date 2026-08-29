<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Dashboard\Listeners;

use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearActivated;
use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearCreated;
use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearDeleted;
use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearUpdated;
use Illuminate\Support\Facades\Cache;

final class ClearDashboardCacheOnYearChange
{
    public function handle(AcademicYearCreated|AcademicYearActivated|AcademicYearUpdated|AcademicYearDeleted $event): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
