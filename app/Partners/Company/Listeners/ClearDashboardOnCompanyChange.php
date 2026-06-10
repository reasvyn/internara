<?php

declare(strict_types=1);

namespace App\Partners\Company\Listeners;

use App\Partners\Company\Events\CompanyCreated;
use Illuminate\Support\Facades\Cache;

final class ClearDashboardOnCompanyChange
{
    public function handle(CompanyCreated $event): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
