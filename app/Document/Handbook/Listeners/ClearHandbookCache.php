<?php

declare(strict_types=1);

namespace App\Document\Handbook\Listeners;

use App\Document\Handbook\Events\HandbookCreated;
use App\Document\Handbook\Events\HandbookDeleted;
use App\Document\Handbook\Events\HandbookUpdated;
use Illuminate\Support\Facades\Cache;

final class ClearHandbookCache
{
    public function handle(HandbookCreated|HandbookUpdated|HandbookDeleted $event): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
