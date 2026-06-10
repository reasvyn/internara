<?php

declare(strict_types=1);

namespace App\Enrollment\Registration\Listeners;

use App\Enrollment\Registration\Events\StudentRegistered;
use Illuminate\Support\Facades\Cache;

final class ClearDashboardOnRegistration
{
    public function handle(StudentRegistered $event): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
