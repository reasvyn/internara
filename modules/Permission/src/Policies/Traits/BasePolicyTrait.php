<?php

declare(strict_types=1);

namespace Modules\Permission\Policies\Traits;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Trait BasePolicyTrait
 *
 * Provides common RBAC protections for all policies:
 * - SuperAdmin protection (cannot be modified/deleted by non-SuperAdmin)
 * - Self-modification allowed
 * - Setup session bypass
 * - Permission enum support
 */
trait BasePolicyTrait
{
    use HandlesAuthorization;

    /**
     * Check if the current session is an authorized setup session.
     */
    protected function isSetupAuthorized(): bool
    {
        return session('setup_authorized') === true;
    }

    /**
     * Resolve permission to string value.
     */
    protected function resolvePermission(?Permission $permission): ?string
    {
        return $permission?->value;
    }

    /**
     * Check if user has permission to perform action.
     */
    protected function hasPermission(User $user, ?Permission $permission): bool
    {
        if ($permission === null) {
            return true;
        }

        return $user->can($permission->value);
    }

    /**
     * Check if user has any of the given permissions.
     */
    protected function hasAnyPermission(User $user, array $permissions): bool
    {
        $permissionValues = array_filter(
            array_map(fn($p) => $p instanceof Permission ? $p->value : $p, $permissions)
        );

        return $user->hasAnyPermission($permissionValues);
    }

    /**
     * Determine if user is a Super Admin.
     */
    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }

    /**
     * Determine if user is an Admin or Super Admin.
     */
    protected function isAdmin(User $user): bool
    {
        return $user->hasAnyRole([Role::SUPER_ADMIN->value, Role::ADMIN->value]);
    }

    /**
     * Determine if user can manage the target (higher authority).
     *
     * Rules:
     * - Super Admin can manage everyone EXCEPT Super Admins
     * - Admin can manage Student, Teacher, Mentor
     * - Others cannot manage anyone
     */
    protected function canManage(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return true;
        }

        if ($this->isSuperAdmin($user)) {
            return !$target->hasRole(Role::SUPER_ADMIN->value);
        }

        if ($this->isAdmin($user)) {
            return $target->hasAnyRole([
                Role::STUDENT->value,
                Role::TEACHER->value,
                Role::MENTOR->value,
            ]);
        }

        return false;
    }

    /**
     * Determine if user can delete the target model.
     *
     * Default: requires 'delete' permission or 'manage' permission.
     */
    protected function canDelete(User $user, ?Permission $deletePermission, ?Permission $managePermission = null): bool
    {
        $managePermission ??= $deletePermission;

        return $user->hasAnyPermission([
            $this->resolvePermission($deletePermission),
            $this->resolvePermission($managePermission),
        ]);
    }

    /**
     * Determine if user can update the target model.
     *
     * Default: requires 'update' permission or 'manage' permission.
     */
    protected function canUpdate(User $user, ?Permission $updatePermission, ?Permission $managePermission = null): bool
    {
        $managePermission ??= $updatePermission;

        return $user->hasAnyPermission([
            $this->resolvePermission($updatePermission),
            $this->resolvePermission($managePermission),
        ]);
    }

    /**
     * Determine if user can create new records.
     *
     * Default: requires 'create' permission or 'manage' permission.
     */
    protected function canCreate(User $user, ?Permission $createPermission, ?Permission $managePermission = null): bool
    {
        $managePermission ??= $createPermission;

        return $user->hasAnyPermission([
            $this->resolvePermission($createPermission),
            $this->resolvePermission($managePermission),
        ]);
    }

    /**
     * Deny with a message.
     */
    protected function deny(string $message = 'You are not authorized to perform this action.'): void
    {
        $this->deny($message);
    }
}