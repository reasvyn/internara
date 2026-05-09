<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\User;

class DashboardService
{
    public function getDashboardForUser(User $user): string
    {
        return match (true) {
            $user->hasAnyRole(['super_admin', 'admin']) => 'dashboard.admin-dashboard',
            $user->hasRole('student') => 'dashboard.student-dashboard',
            $user->hasRole('teacher') => 'dashboard.teacher-dashboard',
            $user->hasRole('supervisor') => 'dashboard.supervisor-dashboard',
            default => 'dashboard.user-dashboard',
        };
    }

    /** @return array<string, mixed> */
    public function getSharedStats(): array
    {
        $user = auth()->user();

        return [
            'user_name' => $user?->name,
            'user_role' => $user?->getRoleNames()->first(),
            'last_login' => $user?->last_login_at,
        ];
    }
}
