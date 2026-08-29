<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Listeners;

use App\Modules\Enrollment\Domain\Registration\Events\StudentRegistered;
use Illuminate\Support\Facades\Cache;

final class ClearDashboardOnRegistration
{
    public function handle(StudentRegistered $event): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
