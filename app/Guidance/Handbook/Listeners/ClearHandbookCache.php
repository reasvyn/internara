<?php

declare(strict_types=1);

namespace App\Guidance\Handbook\Listeners;

use App\Guidance\Handbook\Events\HandbookCreated;
use App\Guidance\Handbook\Events\HandbookDeleted;
use App\Guidance\Handbook\Events\HandbookUpdated;
use Illuminate\Support\Facades\Cache;

final class ClearHandbookCache
{
    public function handle(HandbookCreated|HandbookUpdated|HandbookDeleted $event): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
