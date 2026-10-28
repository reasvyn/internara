<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Observers;

use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use Illuminate\Support\Facades\Cache;

final class CertificateObserver
{
    public function created(Certificate $certificate): void
    {
        $this->invalidate();
    }

    public function updated(Certificate $certificate): void
    {
        $this->invalidate();
    }

    public function deleted(Certificate $certificate): void
    {
        $this->invalidate();
    }

    private function invalidate(): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
