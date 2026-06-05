<?php

declare(strict_types=1);

namespace App\SysAdmin\Observability\Services;

use App\User\Enums\Role;
use App\User\Models\User;

/**
 * Pulse dashboard access guard.
 *
 * Restricts Pulse access to admin-level users only.
 * The dashboard lives under /admin/pulse and should never
 * be exposed to students or other non-admin roles.
 */
final class PulseGuard
{
    /**
     * Determine whether the user can view the Pulse dashboard.
     */
    public static function viewPulse(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole([
            Role::SUPER_ADMIN->value,
            Role::ADMIN->value,
        ]);
    }
}
