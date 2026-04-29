<?php

declare(strict_types=1);

namespace Modules\User\Policies;

use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\Permission\Policies\Traits\BasePolicyTrait;
use Modules\User\Models\User;

/**
 * Class UserPolicy
 *
 * Controls access to User model operations based on hierarchical authority.
 */
class UserPolicy
{
    use BasePolicyTrait;

    /**
     * Determine whether the user can view the user list.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can view a specific user.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $this->canManage($user, $model);
    }

    /**
     * Determine whether the user can create users.
     *
     * @param User|null $user The currently authenticated user.
     * @param array|null $roles The intended roles for the new user.
     */
    public function create(?User $user, ?array $roles = null): bool
    {
        if ($user === null) {
            if ($roles && count($roles) === 1 && in_array(Role::STUDENT->value, $roles)) {
                return true;
            }

            if (!setting('app_installed', false)) {
                return true;
            }

            return false;
        }

        if (!$this->isAdmin($user)) {
            return false;
        }

        if ($roles) {
            foreach ($roles as $role) {
                if (in_array($role, [Role::SUPER_ADMIN->value, Role::ADMIN->value])) {
                    if (!$this->isSuperAdmin($user)) {
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

        return $this->canManage($user, $model);
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        if ($model->hasRole(Role::SUPER_ADMIN->value)) {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        return $this->canManage($user, $model);
    }

    /**
     * Determine whether the user can force delete the user.
     */
    public function forceDelete(User $user, User $model): bool
    {
        if ($model->hasRole(Role::SUPER_ADMIN->value)) {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        return $this->isSuperAdmin($user);
    }

    /**
     * Determine whether the user can restore the user.
     */
    public function restore(User $user, User $model): bool
    {
        if ($model->hasRole(Role::SUPER_ADMIN->value)) {
            return false;
        }

        return $this->canManage($user, $model);
    }
}