<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Observers;

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use Illuminate\Support\Facades\Cache;

final class RegistrationObserver
{
    public function created(Registration $registration): void
    {
        $this->invalidate($registration);
    }

    public function updated(Registration $registration): void
    {
        $this->invalidate($registration);
    }

    public function deleted(Registration $registration): void
    {
        $this->invalidate($registration);
    }

    private function invalidate(Registration $registration): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));

        if ($registration->student_id) {
            Cache::forget(config('cache-keys.dashboard_student').$registration->student_id);
        }

        if ($registration->relationLoaded('mentors')) {
            foreach ($registration->mentors as $mentor) {
                Cache::forget(config('cache-keys.admin_dashboard_stats').'teacher.'.$mentor->id);
            }
        }
    }
}
