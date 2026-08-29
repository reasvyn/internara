<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Partnership\Listeners;

use App\Modules\Partners\Domain\Partnership\Events\PartnershipCreated;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipDeleted;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipRenewed;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipTerminated;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipUpdated;
use Illuminate\Support\Facades\Cache;

final class ClearDashboardOnPartnershipChange
{
    public function handle(
        PartnershipCreated|PartnershipUpdated|PartnershipDeleted|PartnershipRenewed|PartnershipTerminated $event,
    ): void {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
