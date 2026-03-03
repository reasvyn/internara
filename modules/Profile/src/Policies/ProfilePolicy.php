<?php

declare(strict_types=1);

namespace Modules\Profile\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Profile\Models\Profile;
use Modules\User\Models\User;

class ProfilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the profile.
     */
    public function view(User $user, Profile|string|null $profile = null): bool
    {
        if ($user->hasRole(\Modules\Permission\Enums\Role::SUPER_ADMIN->value)) {
            return true;
        }

        if (is_string($profile)) {
            // Check by User UUID if passed
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
        if ($user->hasRole(\Modules\Permission\Enums\Role::SUPER_ADMIN->value)) {
            return true;
        }

        return $user->id === $profile->user_id;
    }
}
