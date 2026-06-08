<?php

declare(strict_types=1);

namespace App\SysAdmin\Observability\Recorders;

use App\Enrollment\Registration\Models\Registration;
use Laravel\Pulse\Facades\Pulse;

/**
 * Records registration lifecycle metrics for Pulse dashboards.
 *
 * Tracks total active registrations, pending verifications,
 * and daily registration volume to surface internship
 * participation trends in the admin Pulse dashboard.
 */
class RegistrationRecorder
{
    /**
     * Events this recorder listens to.
     *
     * @var list<class-string>
     */
    public array $listen = [];

    /**
     * Record current registration state as a snapshot.
     */
    public static function recordSnapshot(): void
    {
        $total = Registration::count();
        $pending = Registration::where('status', 'pending')->count();
        $active = Registration::where('status', 'active')->count();
        $completed = Registration::where('status', 'completed')->count();

        Pulse::record('registrations_total', 'all', $total)->count()->avg()->max();
        Pulse::record('registrations_pending', 'all', $pending)->count()->avg()->max();
        Pulse::record('registrations_active', 'all', $active)->count()->avg()->max();
        Pulse::record('registrations_completed', 'all', $completed)->count()->avg()->max();
    }
}
