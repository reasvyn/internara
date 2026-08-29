<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Handbook\Listeners;

use App\Modules\Document\Domain\Handbook\Events\HandbookCreated;
use App\Modules\Document\Domain\Handbook\Events\HandbookDeleted;
use App\Modules\Document\Domain\Handbook\Events\HandbookUpdated;
use Illuminate\Support\Facades\Cache;

final class ClearHandbookCache
{
    public function handle(HandbookCreated|HandbookUpdated|HandbookDeleted $event): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
