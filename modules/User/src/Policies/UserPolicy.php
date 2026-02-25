<?php

declare(strict_types=1);

namespace Modules\User\Policies;

use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Class UserPolicy
 *
 * Controls access to User model operations based on hierarchical authority.
 */
class UserPolicy
{
    /**
     * Determine whether the user can view the user list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::SUPER_ADMIN->value, Role::ADMIN->value]);
    }

    /**
     * Determine whether the user can view a specific user.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $this->isAuthoritativeOver($user, $model);
    }

    /**
     * Determine whether the user can create users.
     *
     * @param User|null $user The currently authenticated user.
     * @param array|null $roles The intended roles for the new user.
     */
    public function create(?User $user, ?array $roles = null): bool
    {
        // 1. Handle Guest Access (Public Registration or Initial Setup)
        if ($user === null) {
            // Allow if only 'student' role is being assigned (Public Registration)
            if ($roles && count($roles) === 1 && in_array(Role::STUDENT->value, $roles)) {
                return true;
            }

            // Allow any role during initial setup (Super Admin creation)
            if (! setting('app_installed', false)) {
                return true;
            }

            return false;
        }

        // 2. Handle Authenticated User Authority
        // Basic requirement: must be an admin of some sort to create users via Manager
        if (! $user->hasAnyRole([Role::SUPER_ADMIN->value, Role::ADMIN->value])) {
            return false;
        }

        // Role-specific creation authority
        if ($roles) {
            foreach ($roles as $role) {
                // Only Super Admin can create administrative accounts
                if (in_array($role, [Role::SUPER_ADMIN->value, Role::ADMIN->value])) {
                    if (! $user->hasRole(Role::SUPER_ADMIN->value)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $this->isAuthoritativeOver($user, $model);
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        // Protected Accounts
        if ($model->hasRole(Role::SUPER_ADMIN->value) || $user->id === $model->id) {
            return false;
        }

        return $this->isAuthoritativeOver($user, $model);
    }

    /**
     * Internal check for hierarchical authority.
     */
    protected function isAuthoritativeOver(User $subject, User $target): bool
    {
        // Super Admin is authoritative over everyone EXCEPT other Super Admins
        if ($subject->hasRole(Role::SUPER_ADMIN->value)) {
            return ! $target->hasRole(Role::SUPER_ADMIN->value);
        }

        // Admin is authoritative over operational roles only
        if ($subject->hasRole(Role::ADMIN->value)) {
            return $target->hasAnyRole([
                Role::STUDENT->value,
                Role::TEACHER->value,
                Role::MENTOR->value,
            ]);
        }

        return false;
    }
}
