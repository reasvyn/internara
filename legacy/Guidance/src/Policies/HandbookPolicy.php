<?php

declare(strict_types=1);

namespace Modules\Guidance\Policies;

use Modules\Guidance\Models\Handbook;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Class HandbookPolicy
 *
 * Controls access to Handbook model operations.
 */
class HandbookPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            Permission::GUIDANCE_VIEW->value,
            Permission::GUIDANCE_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Handbook $handbook): bool
    {
        return $user->hasAnyPermission([
            Permission::GUIDANCE_VIEW->value,
            Permission::GUIDANCE_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::GUIDANCE_MANAGE->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Handbook $handbook): bool
    {
        return $user->hasPermissionTo(Permission::GUIDANCE_MANAGE->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Handbook $handbook): bool
    {
        return $user->hasPermissionTo(Permission::GUIDANCE_MANAGE->value);
    }

    /**
     * Determine whether the user can force delete the model.
     */
    public function forceDelete(User $user, Handbook $handbook): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }
}
