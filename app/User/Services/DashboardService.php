<?php

declare(strict_types=1);

namespace App\User\Services;

use App\User\Models\User;

class DashboardService
{
    public function getDashboardForUser(User $user): string
    {
        return match (true) {
            $user->hasAnyRole(['super_admin', 'admin']) => 'sysadmin.dashboard',
            $user->hasRole('student') => 'student.dashboard',
            $user->hasRole('teacher') => 'teacher.dashboard',
            $user->hasRole('supervisor') => 'supervisor.dashboard',
            default => 'user.dashboard',
        };
    }

    /**
     * Resolve dashboard route with proxy awareness.
     *
     * Teachers proxying as supervisors see the supervisor dashboard.
     * Admins proxying as teachers/supervisors see the target dashboard.
     */
    public function getProxyDashboardForUser(User $user): ?string
    {
        if ($user->hasRole('teacher')) {
            return 'supervisor.dashboard';
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function getSharedStats(): array
    {
        $user = auth()->user();

        return [
            'user_name' => $user?->name,
            'user_role' => $user?->getRoleNames()->first(),
        ];
    }
}
