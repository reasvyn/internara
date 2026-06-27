<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Listeners;

use App\Partners\Partnership\Events\PartnershipCreated;
use App\Partners\Partnership\Events\PartnershipDeleted;
use App\Partners\Partnership\Events\PartnershipRenewed;
use App\Partners\Partnership\Events\PartnershipTerminated;
use App\Partners\Partnership\Events\PartnershipUpdated;
use Illuminate\Support\Facades\Cache;

final class ClearDashboardOnPartnershipChange
{
    public function handle(
        PartnershipCreated|PartnershipUpdated|PartnershipDeleted|PartnershipRenewed|PartnershipTerminated $event,
    ): void {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
