<?php

declare(strict_types=1);

namespace Modules\Internship\Policies;

use Modules\Internship\Models\InternshipPlacement;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Class InternshipPlacementPolicy
 *
 * Policy for InternshipPlacement model operations.
 */
class InternshipPlacementPolicy
{
    /**
     * Determine whether the user can view any placements.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            Permission::PLACEMENT_VIEW->value,
            Permission::PLACEMENT_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can view the placement.
     */
    public function view(User $user, InternshipPlacement $placement): bool
    {
        return $user->hasAnyPermission([
            Permission::PLACEMENT_VIEW->value,
            Permission::PLACEMENT_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can create placements.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::PLACEMENT_MANAGE->value);
    }

    /**
     * Determine whether the user can update the placement.
     */
    public function update(User $user, InternshipPlacement $placement): bool
    {
        return $user->hasAnyPermission([
            Permission::PLACEMENT_UPDATE->value,
            Permission::PLACEMENT_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can delete the placement.
     */
    public function delete(User $user, InternshipPlacement $placement): bool
    {
        if (!$user->hasPermissionTo(Permission::PLACEMENT_MANAGE->value)) {
            return false;
        }

        return !$placement->student instanceof User || $placement->registrations()->exists();
    }

    /**
     * Determine whether the user can force delete the placement.
     */
    public function forceDelete(User $user, InternshipPlacement $placement): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }
}