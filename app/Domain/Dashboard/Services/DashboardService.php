<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Services;

use App\Domain\User\Models\User;

class DashboardService
{
    /**
     * Get the appropriate dashboard component for the user based on their role.
     */
    public function getDashboardForUser(User $user): string
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return 'dashboard.admin-dashboard';
        }

        if ($user->hasRole('student')) {
            return 'dashboard.student-dashboard';
        }

        if ($user->hasRole('teacher')) {
            return 'dashboard.teacher-dashboard';
        }

        if ($user->hasRole('supervisor')) {
            return 'dashboard.supervisor-dashboard';
        }

        return 'dashboard.user-dashboard';
    }

    /**
     * Get shared stats for all dashboards.
     *
     * @return array<string, mixed>
     */
    public function getSharedStats(): array
    {
        return [
            'user_name' => auth()->user()?->name,
            'user_role' => auth()->user()?->getRoleNames()->first(),
            'last_login' => auth()->user()?->last_login_at,
        ];
    }
}
