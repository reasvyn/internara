<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\User\Models\User;

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
