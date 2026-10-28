<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Placement\Observers;

use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use Illuminate\Support\Facades\Cache;

final class PlacementObserver
{
    public function created(Placement $placement): void
    {
        $this->invalidate();
    }

    public function updated(Placement $placement): void
    {
        $this->invalidate();
    }

    public function deleted(Placement $placement): void
    {
        $this->invalidate();
    }

    private function invalidate(): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
