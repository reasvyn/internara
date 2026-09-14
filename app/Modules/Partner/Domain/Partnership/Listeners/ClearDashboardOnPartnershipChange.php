<?php

declare(strict_types=1);

namespace App\Modules\Partner\Domain\Partnership\Listeners;

use App\Modules\Partner\Domain\Partnership\Events\PartnershipCreated;
use App\Modules\Partner\Domain\Partnership\Events\PartnershipDeleted;
use App\Modules\Partner\Domain\Partnership\Events\PartnershipRenewed;
use App\Modules\Partner\Domain\Partnership\Events\PartnershipTerminated;
use App\Modules\Partner\Domain\Partnership\Events\PartnershipUpdated;
use Illuminate\Support\Facades\Cache;

final class ClearDashboardOnPartnershipChange
{
    public function handle(
        PartnershipCreated|PartnershipUpdated|PartnershipDeleted|PartnershipRenewed|PartnershipTerminated $event,
    ): void {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
