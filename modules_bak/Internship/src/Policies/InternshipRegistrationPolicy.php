<?php

declare(strict_types=1);

namespace Modules\Internship\Policies;

use Modules\Internship\Models\InternshipRegistration;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Class InternshipRegistrationPolicy
 *
 * Policy for InternshipRegistration model.
 */
class InternshipRegistrationPolicy
{
    /**
     * Determine whether the user can view any registrations.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            Permission::REGISTRATION_VIEW->value,
            Permission::REGISTRATION_APPROVE->value,
        ]);
    }

    /**
     * Determine whether the user can view the registration.
     */
    public function view(User $user, InternshipRegistration $registration): bool
    {
        if (!$user->hasPermissionTo(Permission::REGISTRATION_VIEW->value)) {
            return false;
        }

        if ($user->id === $registration->student_id) {
            return true;
        }

        if ($user->id === $registration->teacher_id || $user->id === $registration->mentor_id) {
            return true;
        }

        return $user->hasAnyPermission([
            Permission::REGISTRATION_APPROVE->value,
            Permission::PLACEMENT_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can create registrations.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::REGISTRATION_CREATE->value);
    }

    /**
     * Determine whether the user can update the registration.
     */
    public function update(User $user, InternshipRegistration $registration): bool
    {
        if ($user->id === $registration->student_id) {
            return $user->hasPermissionTo(Permission::REGISTRATION_UPDATE->value);
        }

        return $user->hasAnyPermission([
            Permission::REGISTRATION_UPDATE->value,
            Permission::REGISTRATION_APPROVE->value,
            Permission::PLACEMENT_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can delete the registration.
     */
    public function delete(User $user, InternshipRegistration $registration): bool
    {
        if ($user->id === $registration->student_id) {
            return $user->hasPermissionTo(Permission::REGISTRATION_CANCEL->value);
        }

        return $user->hasPermissionTo(Permission::PLACEMENT_MANAGE->value);
    }

    /**
     * Determine whether the user can restore the registration.
     */
    public function restore(User $user, InternshipRegistration $registration): bool
    {
        return $user->hasPermissionTo(Permission::PLACEMENT_MANAGE->value);
    }

    /**
     * Determine whether the user can force delete the registration.
     */
    public function forceDelete(User $user, InternshipRegistration $registration): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }
}