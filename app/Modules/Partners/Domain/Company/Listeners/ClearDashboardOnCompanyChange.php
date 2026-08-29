<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Company\Listeners;

use App\Modules\Partners\Domain\Company\Events\CompanyCreated;
use App\Modules\Partners\Domain\Company\Events\CompanyDeleted;
use App\Modules\Partners\Domain\Company\Events\CompanyUpdated;
use Illuminate\Support\Facades\Cache;

final class ClearDashboardOnCompanyChange
{
    public function handle(CompanyCreated|CompanyUpdated|CompanyDeleted $event): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
