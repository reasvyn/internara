<?php

declare(strict_types=1);

namespace App\Modules\Journal\Domain\Logbook\Observers;

use App\Modules\Journal\Domain\Logbook\Models\Logbook;
use Illuminate\Support\Facades\Cache;

final class LogbookObserver
{
    public function created(Logbook $logbook): void
    {
        $this->invalidate($logbook);
    }

    public function updated(Logbook $logbook): void
    {
        $this->invalidate($logbook);
    }

    public function deleted(Logbook $logbook): void
    {
        $this->invalidate($logbook);
    }

    private function invalidate(Logbook $logbook): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));

        if ($logbook->user_id) {
            Cache::forget(config('cache-keys.dashboard_student').$logbook->user_id);
        }
    }
}
