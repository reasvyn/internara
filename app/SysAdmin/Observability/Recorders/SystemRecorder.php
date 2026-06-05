<?php

declare(strict_types=1);

namespace App\SysAdmin\Observability\Recorders;

use App\User\Models\User;
use App\User\Notification\Models\Notification;
use Laravel\Pulse\Facades\Pulse;

/**
 * Records user and system activity metrics for Pulse dashboards.
 *
 * Tracks user counts by role, pending notifications, and
 * account status distribution for admin monitoring.
 */
class SystemRecorder
{
    /**
     * Events this recorder listens to.
     *
     * @var list<class-string>
     */
    public array $listen = [];

    /**
     * Record current system state as a snapshot.
     */
    public static function recordSnapshot(): void
    {
        $users = User::count();
        $unreadNotifications = Notification::where('is_read', false)->count();

        Pulse::record('users_total', 'all', $users)->count();
        Pulse::record('notifications_unread', 'all', $unreadNotifications)->count();
    }
}
