<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Modules\User\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DashboardService
{
    public function getDashboardForUser(User $user): string
    {
        return match (true) {
            $user->hasAnyRole(['super_admin', 'admin']) => 'sysadmin.dashboard',
            $user->hasRole('student') => 'student.dashboard',
            $user->hasRole('teacher') => 'teacher.dashboard',
            $user->hasRole('supervisor') => 'supervisor.dashboard',
            // UserDashboard is a base component (template method) — not a routable page.
            // Unknown / missing role is a configuration error; fail closed.
            default => throw new HttpException(403, 'No dashboard assigned for this role.'),
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
