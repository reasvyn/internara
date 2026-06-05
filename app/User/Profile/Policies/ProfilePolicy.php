<?php

declare(strict_types=1);

namespace App\User\Profile\Policies;

use App\Core\Policies\BasePolicy;
use App\User\Profile\Models\Profile;
use App\User\Models\User;

class ProfilePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Profile $profile): bool
    {
        return $this->isAdmin($user) || $this->isOwner($user, $profile);
    }

    public function update(User $user, Profile $profile): bool
    {
        return $this->isAdmin($user) || $this->isOwner($user, $profile);
    }
}
