<?php

declare(strict_types=1);

namespace App\Partners\Company\Listeners;

use App\Partners\Company\Events\CompanyCreated;
use App\Partners\Company\Events\CompanyDeleted;
use App\Partners\Company\Events\CompanyUpdated;
use Illuminate\Support\Facades\Cache;

final class ClearDashboardOnCompanyChange
{
    public function handle(CompanyCreated|CompanyUpdated|CompanyDeleted $event): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
