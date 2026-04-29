<?php

declare(strict_types=1);

namespace Modules\Profile\Policies;

use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\Profile\Models\Profile;
use Modules\User\Models\User;

/**
 * Class ProfilePolicy
 *
 * Controls access to Profile model operations.
 */
class ProfilePolicy
{
    /**
     * Determine whether the user can view any profiles.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::PROFILE_VIEW->value);
    }

    /**
     * Determine whether the user can view the profile.
     */
    public function view(User $user, Profile|string|null $profile = null): bool
    {
        if ($user->hasRole(Role::SUPER_ADMIN->value)) {
            return true;
        }

        if (is_string($profile)) {
            return $user->id === $profile;
        }

        if ($profile instanceof Profile) {
            return $user->id === $profile->user_id;
        }

        return false;
    }

    /**
     * Determine whether the user can update the profile.
     */
    public function update(User $user, Profile $profile): bool
    {
        if ($user->hasRole(Role::SUPER_ADMIN->value)) {
            return true;
        }

        return $user->id === $profile->user_id;
    }

    /**
     * Determine whether the user can delete the profile.
     */
    public function delete(User $user, Profile $profile): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }

    /**
     * Determine whether the user can force delete the profile.
     */
    public function forceDelete(User $user, Profile $profile): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }
}